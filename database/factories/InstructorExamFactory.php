<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorExam>
 */
class InstructorExamFactory extends Factory
{
    protected $model = InstructorExam::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'instructor_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'duration_minutes' => 60,
            'starts_at' => null,
            'ends_at' => null,
            'total_marks' => 100,
            'status' => InstructorExam::STATUS_DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => InstructorExam::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
