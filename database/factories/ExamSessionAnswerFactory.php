<?php

namespace Database\Factories;

use App\Models\Exam\InstructorExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSessionAnswer>
 */
class ExamSessionAnswerFactory extends Factory
{
    protected $model = ExamSessionAnswer::class;

    public function definition(): array
    {
        return [
            'exam_session_id' => ExamSession::factory(),
            'instructor_exam_question_id' => InstructorExamQuestion::factory(),
            'answer_payload' => ['value' => []],
            'score' => null,
            'feedback' => null,
            'answered_at' => now(),
        ];
    }
}
