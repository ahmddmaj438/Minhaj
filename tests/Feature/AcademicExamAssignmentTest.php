<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicExamAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_assignment_accepts_same_day_available_and_due_times(): void
    {
        $instructor = User::factory()->create();
        $studentUser = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS301',
            'name' => 'Database Systems',
            'is_active' => true,
        ]);
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Midterm',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_PUBLISHED,
        ]);

        $response = $this
            ->actingAs($instructor)
            ->from('/academics')
            ->post(route('academics.exam-assignments.store'), [
                'instructor_exam_id' => $exam->id,
                'course_id' => $course->id,
                'student_profile_id' => $student->id,
                'available_at' => '2026-06-02T11:29',
                'due_at' => '2026-06-02T12:29',
                'max_attempts' => 1,
                'status' => 'assigned',
            ]);

        $response
            ->assertRedirect('/academics')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exam_assignments', [
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
        ]);
    }

    public function test_draft_exam_cannot_be_assigned(): void
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS302',
            'name' => 'Database Administration',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Draft Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_DRAFT,
        ]);

        $this->actingAs($instructor)
            ->from('/academics')
            ->post(route('academics.exam-assignments.store'), [
                'instructor_exam_id' => $exam->id,
                'course_id' => $course->id,
                'available_at' => '2026-06-02T11:29',
                'due_at' => '2026-06-02T12:29',
                'max_attempts' => 1,
                'status' => 'assigned',
            ])
            ->assertRedirect('/academics')
            ->assertSessionHasErrors('instructor_exam_id');

        $this->assertDatabaseCount('exam_assignments', 0);
    }
}
