<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_publish_a_ready_exam(): void
    {
        [$instructor, $exam] = $this->readyExam();

        $response = $this
            ->actingAs($instructor)
            ->post(route('instructor.exams.publish.store', $exam));

        $response->assertRedirect(route('instructor.exams.publish.show', $exam, absolute: false));

        $exam->refresh();

        $this->assertSame(InstructorExam::STATUS_PUBLISHED, $exam->status);
        $this->assertNotNull($exam->published_at);
    }

    public function test_instructor_cannot_publish_an_incomplete_exam(): void
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS401',
            'name' => 'Advanced Databases',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Incomplete Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
        ]);

        $response = $this
            ->actingAs($instructor)
            ->from(route('instructor.exams.publish.show', $exam))
            ->post(route('instructor.exams.publish.store', $exam));

        $response
            ->assertRedirect(route('instructor.exams.publish.show', $exam, absolute: false))
            ->assertSessionHasErrors('publish');

        $this->assertDatabaseHas('instructor_exams', [
            'id' => $exam->id,
            'status' => InstructorExam::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function test_instructor_can_return_a_published_exam_to_draft(): void
    {
        [$instructor, $exam] = $this->readyExam();
        $exam->update([
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $response = $this
            ->actingAs($instructor)
            ->patch(route('instructor.exams.publish.draft', $exam));

        $response->assertRedirect(route('instructor.exams.publish.show', $exam, absolute: false));

        $exam->refresh();

        $this->assertSame(InstructorExam::STATUS_DRAFT, $exam->status);
        $this->assertNull($exam->published_at);
    }

    public function test_publish_page_shows_the_five_step_workflow(): void
    {
        [$instructor, $exam] = $this->readyExam();

        $this->actingAs($instructor)
            ->get(route('instructor.exams.publish.show', $exam))
            ->assertOk()
            ->assertSee('1. Exam Information')
            ->assertSee('2. Exam Format')
            ->assertSee('3. Question Management')
            ->assertSee('4. Preview')
            ->assertSee('5. Publish');
    }

    private function readyExam(): array
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS402',
            'name' => 'Database Engineering',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Ready Exam',
            'duration_minutes' => 60,
            'total_marks' => 10,
            'display_format' => InstructorExam::FORMAT_ONE_QUESTION_AT_TIME,
        ]);

        InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain normalization',
            'position' => 1,
            'marks' => 10,
            'prompt' => [
                'status' => 'configured',
                'question_text' => 'Explain third normal form.',
            ],
            'settings' => [],
        ]);

        return [$instructor, $exam];
    }
}
