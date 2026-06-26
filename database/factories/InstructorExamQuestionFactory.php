<?php

namespace Database\Factories;

use App\Models\Exam\InstructorExam;
use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorExamQuestion>
 */
class InstructorExamQuestionFactory extends Factory
{
    protected $model = InstructorExamQuestion::class;

    public function definition(): array
    {
        return [
            'instructor_exam_id' => InstructorExam::factory(),
            'type' => 'mcq',
            'category' => 'objective',
            'title' => fake()->sentence(4),
            'position' => 1,
            'marks' => 10,
            'difficulty' => 'medium',
            'topic' => fake()->word(),
            'prompt' => ['question_text' => fake()->sentence()],
            'settings' => [
                'options' => [
                    ['key' => 'option_1', 'text' => 'Option A', 'is_correct' => true],
                    ['key' => 'option_2', 'text' => 'Option B', 'is_correct' => false],
                ],
            ],
        ];
    }

    public function essay(): static
    {
        return $this->state(fn (): array => [
            'type' => 'essay',
            'category' => 'text',
            'settings' => [
                'rubric' => 'Accuracy, completeness, and clarity.',
                'expected_answer' => 'A complete answer should explain the main concept and give one example.',
            ],
        ]);
    }
}
