<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use App\Models\User;
use App\Services\Exams\Grading\Assistants\WrittenAnswerEvaluationPayload;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_generate_and_approve_review_guidance_for_essay_question(): void
    {
        [$teacher, $exam, $question] = $this->essayQuestionContext();

        $this
            ->actingAs($teacher)
            ->post(route('instructor.exams.questions.essay.guidance', [$exam, $question]))
            ->assertRedirect(route('instructor.exams.questions.essay.edit', [$exam, $question], absolute: false));

        $question->refresh();
        $this->assertSame('draft', $question->settings['review_guidance_status'] ?? null);
        $this->assertNotEmpty($question->settings['key_points'] ?? null);

        $this
            ->actingAs($teacher)
            ->put(route('instructor.exams.questions.essay.update', [$exam, $question]), [
                'question_text' => 'Explain why indexes improve database read performance.',
                'instructions' => 'Use one example.',
                'expected_answer' => 'Indexes improve lookup speed but may increase storage and write cost.',
                'rubric' => 'Correct concept, tradeoff, and example.',
                'key_points' => 'Faster lookup; ordered structure; write/storage tradeoff.',
                'mark_distribution' => '10 marks concept, 5 marks tradeoff, 5 marks example.',
                'common_mistakes' => 'Claims indexes always improve every operation.',
                'evaluation_instructions' => 'Grade content evidence only. Do not grade by answer length.',
                'marks' => 20,
                'display_override' => 'standard',
                'intent' => 'save',
            ])
            ->assertRedirect(route('instructor.exams.questions.essay.edit', [$exam, $question], absolute: false));

        $settings = $question->refresh()->settings;
        $this->assertSame('approved', $settings['review_guidance_status'] ?? null);
        $this->assertSame('Faster lookup; ordered structure; write/storage tradeoff.', $settings['key_points'] ?? null);
    }

    public function test_ai_evaluation_payload_includes_teacher_approved_review_guidance(): void
    {
        [$teacher, $exam, $question] = $this->essayQuestionContext([
            'expected_answer' => 'Indexes improve lookup speed.',
            'rubric' => 'Accuracy and tradeoffs.',
            'key_points' => 'Faster lookup; tradeoff.',
            'mark_distribution' => '10 marks concept, 10 marks tradeoff.',
            'common_mistakes' => 'Ignoring write cost.',
            'evaluation_instructions' => 'Use approved guidance.',
            'review_guidance_status' => 'approved',
        ]);
        $studentUser = User::factory()->create();
        $student = StudentProfile::create([
            'user_id' => $studentUser->id,
            'student_number' => 'RG1001',
            'academic_status' => StudentProfile::STATUS_ACTIVE,
        ]);
        $assignment = ExamAssignment::create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $exam->course_id,
            'student_profile_id' => $student->id,
            'assigned_by' => $teacher->id,
            'status' => ExamAssignment::STATUS_ASSIGNED,
        ]);
        $session = ExamSession::create([
            'exam_assignment_id' => $assignment->id,
            'student_profile_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => ExamSession::STATUS_SUBMITTED,
        ]);
        $answer = ExamSessionAnswer::create([
            'exam_session_id' => $session->id,
            'instructor_exam_question_id' => $question->id,
            'answer_payload' => ['value' => ['response' => 'Indexes speed up reads.']],
        ]);

        $payload = app(WrittenAnswerEvaluationPayload::class)->make($answer);

        $this->assertSame('approved', data_get($payload, 'rubric_and_expected_answer.teacher_approved_review_guidance.status'));
        $this->assertSame('Faster lookup; tradeoff.', data_get($payload, 'rubric_and_expected_answer.teacher_approved_review_guidance.key_points'));
        $this->assertSame('Use approved guidance.', data_get($payload, 'rubric_and_expected_answer.teacher_approved_review_guidance.evaluation_instructions'));
    }

    private function essayQuestionContext(array $settings = []): array
    {
        $teacher = User::factory()->create();
        $course = Course::create([
            'code' => 'DBS601',
            'name' => 'Advanced Databases',
            'is_active' => true,
        ]);
        $exam = InstructorExam::create([
            'course_id' => $course->id,
            'instructor_id' => $teacher->id,
            'title' => 'Review Guidance Exam',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_DRAFT,
        ]);
        $question = InstructorExamQuestion::create([
            'instructor_exam_id' => $exam->id,
            'type' => 'essay',
            'category' => 'text',
            'title' => 'Explain indexing',
            'position' => 1,
            'marks' => 20,
            'prompt' => ['question_text' => 'Explain why indexes improve database read performance.'],
            'settings' => $settings,
        ]);

        return [$teacher, $exam, $question];
    }
}
