<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.course_major.insert') === true;
    }

    public function rules(): array
    {
        return [
            'major_id' => ['required', 'integer', 'exists:majors,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'is_required' => ['nullable', 'boolean'],
            'recommended_level' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'major_id' => 'major',
            'course_id' => 'course',
            'is_required' => 'required course',
            'recommended_level' => 'recommended level',
        ];
    }
}
