<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\Group;
use App\Models\Permission;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_receive_security_headers(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this
            ->from('/login')
            ->post('/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_permission_builder_rejects_tampered_screen_button_and_database_values(): void
    {
        $admin = User::factory()->create();
        $group = Group::create(['name' => 'Auditors', 'slug' => 'auditors']);

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index', ['group' => $group->id]))
            ->put(route('admin.groups.screens.update', $group), [
                'screens' => ['admin.super-users.index', 'not-a-real-screen'],
            ])
            ->assertRedirect(route('admin.access.index', ['group' => $group->id], absolute: false))
            ->assertSessionHasErrors('screens.1');

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index', ['group' => $group->id]))
            ->put(route('admin.groups.buttons.update', $group), [
                'buttons' => ['admin.access.index.save_screens', 'admin.access.index.grant_super_admin'],
            ])
            ->assertRedirect(route('admin.access.index', ['group' => $group->id], absolute: false))
            ->assertSessionHasErrors('buttons.1');

        $this
            ->actingAs($admin)
            ->from(route('admin.access.index', ['group' => $group->id]))
            ->put(route('admin.groups.db.update', $group), [
                'db_permissions' => ['users.select', 'users.drop'],
            ])
            ->assertRedirect(route('admin.access.index', ['group' => $group->id], absolute: false))
            ->assertSessionHasErrors('db_permissions.1');

        $this->assertFalse(Permission::where('name', 'screen.not-a-real-screen.view')->exists());
        $this->assertFalse(Permission::where('name', 'button.admin.access.index.grant_super_admin')->exists());
        $this->assertFalse(Permission::where('name', 'db.users.drop')->exists());
    }

    public function test_student_cannot_start_an_assignment_for_another_student_by_id(): void
    {
        $this->travelTo('2026-06-15 10:00:00');
        [$studentUser, $student, $otherUser, , $course, $instructor] = $this->studentSecurityContext();
        $exam = $this->publishedExam($course, $instructor);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => now()->subHour(),
            'due_at' => now()->addHour(),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this
            ->actingAs($otherUser)
            ->post(route('student.exams.start', $assignment))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('exam_sessions', 0);
        $this->assertAuthenticatedAs($otherUser);
        $this->assertNotSame($studentUser->id, $otherUser->id);
    }

    public function test_student_answer_payload_must_be_an_array_and_respects_size_limits(): void
    {
        $this->travelTo('2026-06-15 10:00:00');
        [$studentUser, $student, , , $course, $instructor] = $this->studentSecurityContext();
        $exam = $this->publishedExam($course, $instructor);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Essay',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['question_text' => 'Explain least privilege.'],
            'settings' => [],
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => now()->subHour(),
            'due_at' => now()->addHour(),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = \App\Models\ExamSession::firstOrFail();

        $this
            ->actingAs($studentUser)
            ->from(route('student.exams.sessions.show', $session))
            ->post(route('student.exams.sessions.answers.save', $session), [
                'answers' => 'not-an-array',
            ])
            ->assertRedirect(route('student.exams.sessions.show', $session, absolute: false))
            ->assertSessionHasErrors('answers');

        $this
            ->actingAs($studentUser)
            ->from(route('student.exams.sessions.show', $session))
            ->post(route('student.exams.sessions.answers.save', $session), [
                'answers' => [
                    $question->id => ['response' => str_repeat('A', 20001)],
                ],
            ])
            ->assertRedirect(route('student.exams.sessions.show', $session, absolute: false))
            ->assertSessionHasErrors("answers.{$question->id}.response");
    }

    public function test_private_local_storage_routes_require_signed_urls(): void
    {
        $this->get('/storage/exam-resources/exams/1/questions/1/topology.pkt')->assertForbidden();
        $this->put('/storage/exam-resources/exams/1/questions/1/topology.pkt', ['content'])->assertForbidden();
    }

    public function test_packet_tracer_upload_rejects_executable_payloads_and_bad_extensions(): void
    {
        Storage::fake('local');
        [$instructor, $exam, $question] = $this->packetTracerQuestion();

        $this
            ->actingAs($instructor)
            ->from(route('instructor.exams.questions.packet-tracer.edit', [$exam, $question]))
            ->put(route('instructor.exams.questions.packet-tracer.update', [$exam, $question]), [
                ...$this->packetTracerPayload(),
                'pkt_file' => UploadedFile::fake()->create('shell.php', 1, 'application/x-httpd-php'),
            ])
            ->assertRedirect(route('instructor.exams.questions.packet-tracer.edit', [$exam, $question], absolute: false))
            ->assertSessionHasErrors('pkt_file');

        Storage::disk('local')->assertDirectoryEmpty('/');
    }

    public function test_packet_tracer_upload_is_stored_privately_with_sanitized_metadata(): void
    {
        Storage::fake('local');
        [$instructor, $exam, $question] = $this->packetTracerQuestion();

        $this
            ->actingAs($instructor)
            ->put(route('instructor.exams.questions.packet-tracer.update', [$exam, $question]), [
                ...$this->packetTracerPayload(),
                'pkt_file' => UploadedFile::fake()->create('..\\network.pkt', 1, 'application/octet-stream'),
            ])
            ->assertRedirect(route('instructor.exams.questions.packet-tracer.edit', [$exam, $question], absolute: false));

        $settings = $question->refresh()->settings;

        $this->assertSame('local', $settings['pkt_file']['disk']);
        $this->assertStringStartsWith("exam-resources/exams/{$exam->id}/questions/{$question->id}/", $settings['pkt_file']['path']);
        $this->assertSame('network.pkt', $settings['pkt_file']['original_name']);
        Storage::disk('local')->assertExists($settings['pkt_file']['path']);
    }

    private function studentSecurityContext(): array
    {
        $instructor = User::factory()->create();
        $studentUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = Course::create([
            'code' => 'SEC501',
            'name' => 'Secure Applications',
            'is_active' => true,
        ]);
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'SEC1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $otherStudent = StudentProfile::create([
            'user_id' => $otherUser->id,
            'student_number' => 'SEC1002',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);

        foreach ([$student, $otherStudent] as $profile) {
            $profile->courses()->attach($course->id, [
                'enrollment_status' => 'enrolled',
                'enrolled_at' => now(),
            ]);
        }

        return [$studentUser, $student, $otherUser, $otherStudent, $course, $instructor];
    }

    private function publishedExam(Course $course, User $instructor): InstructorExam
    {
        return InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Security Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_PUBLISHED,
        ]);
    }

    private function packetTracerQuestion(): array
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'NET501',
            'name' => 'Network Security',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Packet Tracer Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_DRAFT,
        ]);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'packet_tracer',
            'category' => 'networking',
            'title' => 'Configure ACLs',
            'position' => 1,
            'marks' => 10,
            'prompt' => ['status' => 'type_selected'],
            'settings' => [],
        ]);

        return [$instructor, $exam, $question];
    }

    private function packetTracerPayload(): array
    {
        return [
            'scenario' => 'Configure ACLs for the topology.',
            'instructions' => 'Use the provided topology.',
            'expected_tasks' => 'ACLs configured and verified.',
            'configuration_notes' => 'No device renaming.',
            'marks' => 10,
            'difficulty' => 'medium',
            'topic' => 'ACL',
            'display_override' => 'attachment_focused',
            'intent' => 'save',
        ];
    }
}
