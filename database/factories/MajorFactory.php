<?php

namespace Database\Factories;

use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Major>
 */
class MajorFactory extends Factory
{
    protected $model = Major::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PRG##')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
