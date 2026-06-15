<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.course_student.insert') === true;
    }

    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'integer', 'exists:student_profiles,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'enrollment_status' => ['required', Rule::in(['enrolled', 'completed', 'dropped', 'pending'])],
            'enrolled_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_profile_id' => 'student',
            'course_id' => 'course',
            'enrollment_status' => 'enrollment status',
            'enrolled_at' => 'enrollment date',
        ];
    }
}
