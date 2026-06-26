<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
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

    public function test_instructor_cannot_return_exam_with_student_attempts_to_draft(): void
    {
        [$instructor, $exam] = $this->readyExam();
        $exam->update([
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $session = $this->submittedSessionForExam($exam, $instructor);

        $this
            ->actingAs($instructor)
            ->from(route('instructor.exams.publish.show', $exam))
            ->patch(route('instructor.exams.publish.draft', $exam))
            ->assertRedirect(route('instructor.exams.publish.show', $exam, absolute: false))
            ->assertSessionHasErrors('publish');

        $this->assertSame(ExamSession::STATUS_SUBMITTED, $session->refresh()->status);
        $this->assertSame(InstructorExam::STATUS_PUBLISHED, $exam->refresh()->status);
    }

    public function test_instructor_cannot_remove_published_or_used_exam(): void
    {
        [$instructor, $exam] = $this->readyExam();
        $exam->update([
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this
            ->actingAs($instructor)
            ->from(route('instructor.exams.edit', $exam))
            ->delete(route('instructor.exams.destroy', $exam))
            ->assertRedirect(route('instructor.exams.edit', $exam, absolute: false))
            ->assertSessionHasErrors('exam');

        $this->assertDatabaseHas('instructor_exams', [
            'id' => $exam->id,
            'status' => InstructorExam::STATUS_PUBLISHED,
        ]);
    }

    public function test_publish_page_shows_the_five_step_workflow(): void
    {
        [$instructor, $exam] = $this->readyExam();

        $this->actingAs($instructor)
            ->get(route('instructor.exams.publish.show', $exam))
            ->assertOk()
            ->assertSee('1. Exam Header')
            ->assertSee('2. Instructions')
            ->assertSee('3. Questions')
            ->assertSee('4. Review')
            ->assertSee('5. Preview and Publish');
    }

    public function test_publish_page_shows_exact_question_problems(): void
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS405',
            'name' => 'Publish Readiness',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Problem Exam',
            'description' => 'Read all questions carefully.',
            'duration_minutes' => 60,
            'total_marks' => 10,
        ]);

        InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Broken MCQ',
            'position' => 1,
            'marks' => 10,
            'prompt' => [
                'status' => 'configured',
                'question_text' => 'Choose the database key.',
            ],
            'settings' => [
                'allow_multiple_correct' => false,
                'options' => [
                    ['key' => 'option_1', 'text' => 'Primary key', 'is_correct' => false],
                    ['key' => 'option_2', 'text' => 'Foreign key', 'is_correct' => false],
                ],
            ],
        ]);

        $this
            ->actingAs($instructor)
            ->get(route('instructor.exams.publish.show', $exam))
            ->assertOk()
            ->assertSee('Question problems')
            ->assertSee('Question 1')
            ->assertSee('Broken MCQ')
            ->assertSee('Choose the database key.')
            ->assertSee('Select exactly one correct answer.')
            ->assertSee('Fix this question');
    }

    public function test_mcq_edit_keeps_saved_correct_answer_selected(): void
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS406',
            'name' => 'MCQ Persistence',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'MCQ Exam',
            'duration_minutes' => 60,
            'total_marks' => 10,
        ]);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Saved MCQ',
            'position' => 1,
            'marks' => 10,
            'prompt' => [
                'status' => 'configured',
                'question_text' => 'Which one is correct?',
            ],
            'settings' => [
                'allow_multiple_correct' => false,
                'shuffle_options' => true,
                'options' => [
                    ['key' => 'option_1', 'text' => 'Wrong answer', 'is_correct' => false],
                    ['key' => 'option_2', 'text' => 'Right answer', 'is_correct' => true],
                ],
            ],
        ]);

        $this
            ->actingAs($instructor)
            ->get(route('instructor.exams.questions.mcq.edit', [$exam, $question]))
            ->assertOk()
            ->assertSee('Right answer')
            ->assertSee('correctSingle: \'1\'', false);
    }

    public function test_instructor_can_duplicate_a_question_from_builder(): void
    {
        [$instructor, $exam] = $this->readyExam();
        $question = $exam->questions()->firstOrFail();

        $response = $this
            ->actingAs($instructor)
            ->post(route('instructor.exams.questions.duplicate', [$exam, $question]));

        $response->assertRedirect(route('instructor.exams.questions.order.index', $exam, absolute: false));

        $this->assertSame(2, $exam->questions()->count());
        $this->assertDatabaseHas('instructor_exam_questions', [
            'instructor_exam_id' => $exam->id,
            'title' => 'Copy of Explain normalization',
            'position' => 2,
            'marks' => 10,
        ]);
    }

    public function test_instructor_can_add_existing_question_from_question_bank(): void
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS403',
            'name' => 'Question Bank Databases',
            'is_active' => true,
        ]);
        $sourceExam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Source Exam',
            'duration_minutes' => 60,
            'total_marks' => 10,
        ]);
        $targetExam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Target Exam',
            'duration_minutes' => 60,
            'total_marks' => 10,
        ]);
        $sourceQuestion = InstructorExamQuestion::create([
            'instructor_exam_id' => $sourceExam->id,
            'type' => 'mcq',
            'category' => 'objective',
            'title' => 'Saved MCQ',
            'position' => 1,
            'marks' => 5,
            'save_to_bank' => true,
            'tcexam_question_id' => 123,
            'tcexam_subject_id' => 456,
            'prompt' => [
                'status' => 'configured',
                'question_text' => 'Which key uniquely identifies a row?',
            ],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'Primary key', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'Foreign key', 'is_correct' => false],
                ],
            ],
        ]);

        $this
            ->actingAs($instructor)
            ->get(route('instructor.exams.question-types.index', $targetExam))
            ->assertOk()
            ->assertSee('Question Bank')
            ->assertSee('Saved MCQ')
            ->assertSee('Add from question bank');

        $response = $this
            ->actingAs($instructor)
            ->post(route('instructor.exams.questions.bank.store', $targetExam), [
                'bank_question_id' => $sourceQuestion->id,
            ]);

        $response
            ->assertRedirect(route('instructor.exams.questions.order.index', $targetExam, absolute: false))
            ->assertSessionHas('status', 'Question added from the question bank. Review it before publishing.');

        $this->assertSame(1, $sourceExam->questions()->count());
        $this->assertSame(1, $targetExam->questions()->count());

        $copy = $targetExam->questions()->firstOrFail();

        $this->assertNotSame($sourceQuestion->id, $copy->id);
        $this->assertSame('Saved MCQ', $copy->title);
        $this->assertSame('Which key uniquely identifies a row?', $copy->prompt['question_text']);
        $this->assertSame('Primary key', $copy->settings['options'][0]['text']);
        $this->assertFalse((bool) $copy->save_to_bank);
        $this->assertNotSame($sourceQuestion->refresh()->tcexam_question_id, $copy->tcexam_question_id);
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

    private function submittedSessionForExam(InstructorExam $exam, User $instructor): ExamSession
    {
        $studentUser = User::factory()->create();
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'S'.fake()->unique()->numberBetween(1000, 9999),
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $student->courses()->attach($exam->course_id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $exam->course_id,
            'student_profile_id' => $student->id,
            'assigned_by' => $instructor->id,
            'available_at' => now()->subHour(),
            'due_at' => now()->addHour(),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);

        return ExamSession::create([
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(5),
            'status' => ExamSession::STATUS_SUBMITTED,
            'max_score' => 10,
        ]);
    }
}
