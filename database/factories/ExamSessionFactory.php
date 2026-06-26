<?php

namespace Database\Factories;

use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        return [
            'exam_assignment_id' => ExamAssignment::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => null,
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'score' => null,
            'max_score' => null,
            'percentage' => null,
            'passed' => null,
            'metadata' => [],
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'submitted_at' => now(),
            'status' => ExamSession::STATUS_SUBMITTED,
        ]);
    }
}
