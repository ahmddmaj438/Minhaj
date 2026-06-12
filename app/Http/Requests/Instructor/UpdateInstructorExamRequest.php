<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\ExamDisplayFormatCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstructorExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('is_active', true)],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'starts_at' => ['nullable', 'required_with:ends_at', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'total_marks' => ['required', 'numeric', 'min:1', 'max:9999.99'],
            'display_format' => ['required', Rule::in(ExamDisplayFormatCatalog::keys())],
        ];
    }

    public function attributes(): array
    {
        return [
            'course_id' => 'course',
            'duration_minutes' => 'duration',
            'starts_at' => 'start date and time',
            'ends_at' => 'end date and time',
            'total_marks' => 'total marks',
            'display_format' => 'exam format',
        ];
    }
}
