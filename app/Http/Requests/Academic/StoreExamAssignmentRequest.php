<?php

namespace App\Http\Requests\Academic;

use App\Models\ExamAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.exam_assignments.insert') === true;
    }

    public function rules(): array
    {
        return [
            'instructor_exam_id' => ['required', 'integer', 'exists:instructor_exams,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'student_profile_id' => ['nullable', 'integer', 'exists:student_profiles,id'],
            'available_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'show_score_to_student' => ['nullable', 'boolean'],
            'show_feedback_to_student' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([
                ExamAssignment::STATUS_ASSIGNED,
                ExamAssignment::STATUS_OPEN,
                ExamAssignment::STATUS_CLOSED,
                ExamAssignment::STATUS_CANCELLED,
            ])],
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_exam_id' => 'exam',
            'course_id' => 'course',
            'student_profile_id' => 'student',
            'available_at' => 'available time',
            'due_at' => 'due time',
            'max_attempts' => 'maximum attempts',
            'show_score_to_student' => 'show score to student',
            'show_feedback_to_student' => 'show feedback to student',
        ];
    }
}
