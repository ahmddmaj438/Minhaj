<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Exam\InstructorExam;
use App\Models\ExamAssignment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAssignment>
 */
class ExamAssignmentFactory extends Factory
{
    protected $model = ExamAssignment::class;

    public function definition(): array
    {
        return [
            'instructor_exam_id' => InstructorExam::factory()->published(),
            'course_id' => Course::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'assigned_by' => User::factory(),
            'available_at' => now()->subHour(),
            'due_at' => now()->addHour(),
            'max_attempts' => 1,
            'status' => ExamAssignment::STATUS_ASSIGNED,
            'settings' => [
                'show_score_to_student' => false,
                'show_feedback_to_student' => false,
            ],
        ];
    }
}
