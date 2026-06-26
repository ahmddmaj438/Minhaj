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

class AiGradingHumanReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_suggestion_is_not_final_grade_until_teacher_saves_manual_score(): void
    {
        $this->travelTo('2026-06-02 10:00:00');

        $teacher = User::factory()->create(['name' => 'Dr Rana']);
        $studentUser = User::factory()->create(['name' => 'Leen Ali']);
        $course = Course::factory()->create(['code' => 'DBS301', 'name' => 'Database Systems']);
        $course->teachers()->attach($teacher->id, [
            'role' => 'teacher',
            'assigned_by' => $teacher->id,
            'assigned_at' => now(),
        ]);
        $student = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'major_id' => null,
            'student_number' => 'S1001',
        ]);
        $student->courses()->attach($course->id, [
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $exam = InstructorExam::factory()->published()->create([
            'course_id' => $course->id,
            'instructor_id' => $teacher->id,
            'title' => 'Essay Review Exam',
            'total_marks' => 20,
        ]);
        $question = InstructorExamQuestion::factory()->essay()->create([
            'instructor_exam_id' => $exam->id,
            'title' => 'Explain database indexes',
            'marks' => 20,
        ]);
        $assignment = ExamAssignment::factory()->create([
            'instructor_exam_id' => $exam->id,
            'course_id' => $course->id,
            'student_profile_id' => $student->id,
            'assigned_by' => $teacher->id,
        ]);

        $this->actingAs($studentUser)->post(route('student.exams.start', $assignment));
        $session = ExamSession::firstOrFail();
        $this
            ->actingAs($studentUser)
            ->post(route('student.exams.sessions.submit', $session), [
                'answers' => [
                    $question->id => [
                        'response' => 'Indexes help the database find matching rows faster.',
                    ],
                ],
            ]);
        $answer = $session->refresh()->answers()->where('instructor_exam_question_id', $question->id)->firstOrFail();

        $this
            ->actingAs($teacher)
            ->postJson(route('instructor.grading.api.answers.assist-browser', [$session, $answer]), [
                'suggested_score' => 16,
                'max_score' => 20,
                'confidence' => 0.72,
                'feedback' => 'Mostly correct, but the answer should include tradeoffs.',
                'rationale' => 'The answer explains lookup speed but omits write and storage cost.',
                'strengths' => ['Explains faster lookup'],
                'improvements' => ['Mention write overhead'],
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.suggested_score', 16);

        $answer->refresh();
        $session->refresh();
        $this->assertNull($answer->score);
        $this->assertSame('manual_pending', $answer->answer_payload['status'] ?? null);
        $this->assertEquals(16.0, $answer->answer_payload['ai_grading_suggestion']['suggested_score'] ?? null);
        $this->assertTrue((bool) ($session->metadata['manual_grading_pending'] ?? false));

        $this
            ->actingAs($teacher)
            ->put(route('instructor.grading.answers.update', [$session, $answer]), [
                'score' => 18,
                'feedback' => 'Teacher reviewed and awarded extra credit for a clear example.',
            ])
            ->assertRedirect();

        $answer->refresh();
        $session->refresh();
        $this->assertSame('18.00', (string) $answer->score);
        $this->assertSame('manual_graded', $answer->answer_payload['status'] ?? null);
        $this->assertEquals(16.0, $answer->answer_payload['ai_grading_suggestion']['suggested_score'] ?? null);
        $this->assertSame('90.00', (string) $session->percentage);
        $this->assertFalse((bool) ($session->metadata['manual_grading_pending'] ?? true));
    }
}
