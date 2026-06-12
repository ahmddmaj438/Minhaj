<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamActivityLog;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamTakingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_start_an_exam_outside_its_time_window(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $futureExam = $this->exam($course, $instructor, ['title' => 'Future Exam']);
        $closedExam = $this->exam($course, $instructor, ['title' => 'Closed Exam']);

        $future = $this->assignment($futureExam, $course, $student, $instructor, [
            'available_at' => '2026-06-12 11:00:00',
            'due_at' => '2026-06-12 13:00:00',
        ]);
        $closed = $this->assignment($closedExam, $course, $student, $instructor, [
            'available_at' => '2026-06-12 07:00:00',
            'due_at' => '2026-06-12 09:00:00',
        ]);

        $this->actingAs($studentUser)
            ->post(route('student.exams.start', $future))
            ->assertSessionHasErrors('exam');

        $this->actingAs($studentUser)
            ->post(route('student.exams.start', $closed))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('exam_sessions', 0);
    }

    public function test_all_supported_question_answers_are_saved_as_drafts(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor);

        $mcq = $this->question($exam, 'mcq', 'objective', 1);
        $trueFalse = $this->question($exam, 'true_false', 'objective', 2);
        $matching = $this->question($exam, 'matching', 'objective', 3);
        $fillBlank = $this->question($exam, 'fill_blank', 'text', 4);
        $essay = $this->question($exam, 'essay', 'text', 5);
        $coding = $this->question($exam, 'sql', 'coding', 6);
        $networking = $this->question($exam, 'packet_tracer', 'networking', 7);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();

        $this->actingAs($studentUser)
            ->post(route('student.exams.sessions.answers.save', $session), [
                'answers' => [
                    $mcq->id => ['selected_options' => ['option_2']],
                    $trueFalse->id => ['answer' => 'false'],
                    $matching->id => ['matches' => ['Definition A', 'Definition B']],
                    $fillBlank->id => ['blanks' => ['Laravel', 'Blade']],
                    $essay->id => ['response' => 'A structured essay response.'],
                    $coding->id => ['response' => 'SELECT * FROM courses;'],
                    $networking->id => ['response' => 'Configured VLANs and verified connectivity.'],
                ],
            ])
            ->assertRedirect();

        $answers = $session->answers()->get()->keyBy('instructor_exam_question_id');

        $this->assertSame(['option_2'], $answers[$mcq->id]->answer_payload['value']['selected_options']);
        $this->assertSame('false', $answers[$trueFalse->id]->answer_payload['value']['answer']);
        $this->assertSame(['Definition A', 'Definition B'], $answers[$matching->id]->answer_payload['value']['matches']);
        $this->assertSame(['Laravel', 'Blade'], $answers[$fillBlank->id]->answer_payload['value']['blanks']);
        $this->assertSame('A structured essay response.', $answers[$essay->id]->answer_payload['value']['response']);
        $this->assertSame('SELECT * FROM courses;', $answers[$coding->id]->answer_payload['value']['response']);
        $this->assertSame('Configured VLANs and verified connectivity.', $answers[$networking->id]->answer_payload['value']['response']);
    }

    public function test_one_question_navigation_and_timer_controls_render(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor, [
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
            'duration_minutes' => 30,
        ]);
        $this->question($exam, 'mcq', 'objective', 1);
        $this->question($exam, 'essay', 'text', 2);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();

        $this->assertTrue($session->expires_at->equalTo(now()->addMinutes(30)));

        $this->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session))
            ->assertOk()
            ->assertSee('x-text="current + 1"', false)
            ->assertSee('of 2')
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Flag question')
            ->assertSee('Time remaining')
            ->assertSee('minhaj-exam-expired', false)
            ->assertSee('form.requestSubmit(submitButton)', false);
    }

    public function test_student_resumes_the_same_active_exam_session(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor);
        $this->question($exam, 'essay', 'text', 1);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $firstSession = ExamSession::firstOrFail();

        $this->travel(5)->minutes();

        $response = $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));

        $response->assertRedirect(route('student.exams.sessions.show', $firstSession, absolute: false));
        $this->assertDatabaseCount('exam_sessions', 1);
        $this->assertDatabaseHas('exam_activity_logs', [
            'exam_session_id' => $firstSession->id,
            'event' => ExamActivityLog::EVENT_RESUMED,
        ]);
    }

    public function test_timeout_submission_preserves_answers_and_submits_the_exam(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor, ['duration_minutes' => 5]);
        $question = $this->question($exam, 'essay', 'text', 1);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();

        $this->travel(6)->minutes();

        $this->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'auto_submit' => '1',
                'answers' => [
                    $question->id => ['response' => 'Answer present when the timer expired.'],
                ],
            ])
            ->assertRedirect(route('student.exams.index', absolute: false));

        $session->refresh();
        $answer = $session->answers()->where('instructor_exam_question_id', $question->id)->firstOrFail();

        $this->assertSame(ExamSession::STATUS_SUBMITTED, $session->status);
        $this->assertTrue($session->metadata['timed_out']);
        $this->assertSame('Answer present when the timer expired.', $answer->answer_payload['value']['response']);
        $this->assertDatabaseHas('exam_activity_logs', [
            'exam_session_id' => $session->id,
            'event' => ExamActivityLog::EVENT_SUBMITTED,
        ]);
    }

    public function test_opening_an_expired_session_marks_it_expired_and_logs_the_event(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor, ['duration_minutes' => 5]);
        $this->question($exam, 'essay', 'text', 1);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();
        $this->travel(6)->minutes();

        $this->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session))
            ->assertRedirect(route('student.exams.index', absolute: false))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'status' => ExamSession::STATUS_EXPIRED,
        ]);
        $this->assertDatabaseHas('exam_activity_logs', [
            'exam_session_id' => $session->id,
            'event' => ExamActivityLog::EVENT_EXPIRED,
        ]);
    }

    public function test_another_student_cannot_access_or_change_the_exam_session(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        [$otherUser] = $this->student($course, 'S2002');
        $exam = $this->exam($course, $instructor);
        $question = $this->question($exam, 'essay', 'text', 1);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();

        $this->actingAs($otherUser)
            ->get(route('student.exams.sessions.show', $session))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('student.exams.sessions.answers.save', $session), [
                'answers' => [$question->id => ['response' => 'Unauthorized change']],
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('student.exams.sessions.submit', $session))
            ->assertForbidden();

        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $session->refresh()->status);
        $this->assertNull($session->answers()->firstOrFail()->answered_at);
    }

    public function test_activity_logs_cover_start_save_and_submit_transitions(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor);
        $question = $this->question($exam, 'mcq', 'objective', 1);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();
        $answer = [$question->id => ['selected_options' => ['option_1']]];

        $this->actingAs($studentUser)
            ->post(route('student.exams.sessions.answers.save', $session), ['answers' => $answer]);
        $this->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), ['answers' => $answer]);

        $this->assertSame(
            [
                ExamActivityLog::EVENT_STARTED,
                ExamActivityLog::EVENT_ANSWERS_SAVED,
                ExamActivityLog::EVENT_SUBMITTED,
            ],
            $session->activityLogs()->orderBy('id')->pluck('event')->all()
        );
    }

    public function test_draft_exam_is_hidden_and_cannot_be_started_or_reopened(): void
    {
        $this->travelTo('2026-06-12 10:00:00');
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor, [
            'title' => 'Unpublished Draft Exam',
            'status' => InstructorExam::STATUS_DRAFT,
        ]);
        $assignment = $this->assignment($exam, $course, $student, $instructor);

        $this->actingAs($studentUser)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertDontSee('Unpublished Draft Exam');

        $this->actingAs($studentUser)
            ->post(route('student.exams.start', $assignment))
            ->assertSessionHasErrors('exam');

        $session = ExamSession::create([
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => ExamSession::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($studentUser)
            ->get(route('student.exams.sessions.show', $session))
            ->assertNotFound();
    }

    public function test_instructor_preview_identifies_exam_format_and_question_override(): void
    {
        [$studentUser, $student, $course, $instructor] = $this->context();
        $exam = $this->exam($course, $instructor, [
            'display_format' => InstructorExam::FORMAT_GOOGLE_FORMS,
        ]);
        $question = $this->question($exam, 'essay', 'text', 1);
        $question->update(['display_override' => 'expanded_essay']);

        $this->actingAs($instructor)
            ->get(route('instructor.exams.preview.show', $exam))
            ->assertOk()
            ->assertSee('Google Forms Style')
            ->assertSee('Expanded essay layout');
    }

    private function context(): array
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS'.fake()->unique()->numberBetween(400, 999),
            'name' => 'Database Systems',
            'is_active' => true,
        ]);
        [$studentUser, $student] = $this->student($course, 'S1001');

        return [$studentUser, $student, $course, $instructor];
    }

    private function student(Course $course, string $number): array
    {
        $user = User::factory()->create();
        $student = StudentProfile::create([
            'user_id' => $user->id,
            'student_number' => $number,
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        return [$user, $student];
    }

    private function exam(Course $course, User $instructor, array $overrides = []): InstructorExam
    {
        return InstructorExam::create(array_merge([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Student Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
            'status' => InstructorExam::STATUS_PUBLISHED,
        ], $overrides));
    }

    private function assignment(
        InstructorExam $exam,
        Course $course,
        StudentProfile $student,
        User $instructor,
        array $overrides = []
    ): ExamAssignment {
        return ExamAssignment::create(array_merge([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => now()->subHour(),
            'due_at' => now()->addHours(2),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ], $overrides));
    }

    private function question(
        InstructorExam $exam,
        string $type,
        string $category,
        int $position
    ): InstructorExamQuestion {
        $settings = match ($type) {
            'mcq' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'B', 'is_correct' => false],
                ],
            ],
            'matching' => [
                'pairs' => [
                    ['left' => 'Term A', 'right' => 'Definition A'],
                    ['left' => 'Term B', 'right' => 'Definition B'],
                ],
            ],
            'fill_blank' => [
                'blanks' => [
                    ['label' => 'Framework', 'accepted_answers' => ['Laravel']],
                    ['label' => 'Template', 'accepted_answers' => ['Blade']],
                ],
            ],
            default => [],
        };

        return InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => $type,
            'category' => $category,
            'title' => 'Question '.$position,
            'position' => $position,
            'marks' => 10,
            'prompt' => ['question_text' => 'Question '.$position],
            'settings' => $settings,
        ]);
    }
}
