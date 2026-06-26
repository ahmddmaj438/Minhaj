<?php

namespace Database\Factories;

use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'major_id' => Major::factory(),
            'student_number' => strtoupper(fake()->unique()->bothify('S####')),
            'academic_status' => StudentProfile::STATUS_ACTIVE,
            'admission_year' => 2026,
            'metadata' => [],
        ];
    }
}
