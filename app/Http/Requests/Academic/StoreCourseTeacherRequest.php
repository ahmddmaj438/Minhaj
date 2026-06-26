<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.course_user.insert') === true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'course_id' => 'course',
            'user_id' => 'teacher',
        ];
    }
}
