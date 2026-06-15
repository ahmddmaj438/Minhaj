<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Academics\AcademicWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_creation_rejects_due_time_before_available_time(): void
    {
        [$instructor, $student, $course, $exam] = $this->assignmentContext();

        $this->expectException(ValidationException::class);

        app(AcademicWorkflowService::class)->createExamAssignment([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'available_at' => '2026-06-15T12:00',
            'due_at' => '2026-06-15T11:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
            'show_score_to_student' => false,
            'show_feedback_to_student' => false,
        ], $instructor->id, false, false);
    }

    public function test_assignment_creation_rejects_duplicate_student_assignment(): void
    {
        [$instructor, $student, $course, $exam] = $this->assignmentContext();
        $service = app(AcademicWorkflowService::class);
        $payload = [
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'available_at' => '2026-06-15T10:00',
            'due_at' => '2026-06-15T12:00',
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
            'show_score_to_student' => true,
            'show_feedback_to_student' => true,
        ];

        $service->createExamAssignment($payload, $instructor->id, true, true);

        try {
            $service->createExamAssignment($payload, $instructor->id, true, true);
            $this->fail('Duplicate assignment was not rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_profile_id', $exception->errors());
        }

        $this->assertDatabaseCount('exam_assignments', 1);
    }

    public function test_session_status_update_sets_submission_time_once(): void
    {
        [$instructor, $student, $course, $exam] = $this->assignmentContext();
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        $session = ExamSession::create([
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => ExamSession::STATUS_IN_PROGRESS,
        ]);

        app(AcademicWorkflowService::class)->updateSessionStatus($session, ExamSession::STATUS_SUBMITTED);

        $this->assertSame(ExamSession::STATUS_SUBMITTED, $session->refresh()->status);
        $this->assertNotNull($session->submitted_at);
    }

    private function assignmentContext(): array
    {
        $instructor = User::factory()->create();
        $studentUser = User::factory()->create();
        $course = Course::create([
            'code' => 'ARCH501',
            'name' => 'Enterprise Architecture',
            'is_active' => true,
        ]);
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'EA1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Architecture Midterm',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_PUBLISHED,
        ]);

        return [$instructor, $student, $course, $exam];
    }
}
