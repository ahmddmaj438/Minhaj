<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorExamAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_cannot_edit_another_instructors_exam(): void
    {
        [$owner, $exam] = $this->examOwnedByInstructor();
        $otherInstructor = User::factory()->create();

        $this
            ->actingAs($otherInstructor)
            ->get(route('instructor.exams.edit', $exam))
            ->assertForbidden();

        $this
            ->actingAs($otherInstructor)
            ->put(route('instructor.exams.update', $exam), [
                'title' => 'Unauthorized title',
                'course_id' => $exam->course_id,
                'duration_minutes' => 60,
                'total_marks' => 100,
                'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
            ])
            ->assertForbidden();

        $this->assertSame($owner->id, $exam->refresh()->instructor_id);
        $this->assertNotSame('Unauthorized title', $exam->title);
    }

    public function test_question_route_rejects_question_from_another_exam(): void
    {
        [$owner, $exam] = $this->examOwnedByInstructor();
        [, $otherExam] = $this->examOwnedByInstructor();
        $foreignQuestion = InstructorExamQuestion::create([
            'instructor_exam_id' => $otherExam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Foreign question',
            'position' => 1,
            'marks' => 1,
            'prompt' => ['status' => 'type_selected'],
            'settings' => [],
        ]);

        $this
            ->actingAs($owner)
            ->get(route('instructor.exams.questions.mcq.edit', [$exam, $foreignQuestion]))
            ->assertNotFound();
    }

    private function examOwnedByInstructor(): array
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'SEC'.fake()->unique()->numberBetween(100, 999),
            'name' => 'Secure Systems',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Secure Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
            'status' => InstructorExam::STATUS_DRAFT,
        ]);

        return [$instructor, $exam];
    }
}
