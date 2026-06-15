<?php

namespace App\Http\Requests\Academic;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.student_profiles.insert') === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:student_profiles,user_id'],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'student_number' => ['required', 'string', 'max:100', 'unique:student_profiles,student_number'],
            'academic_status' => ['required', Rule::in([
                StudentProfile::STATUS_ACTIVE,
                StudentProfile::STATUS_INACTIVE,
                StudentProfile::STATUS_GRADUATED,
                StudentProfile::STATUS_SUSPENDED,
            ])],
            'admission_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'student user',
            'major_id' => 'major',
            'student_number' => 'student number',
            'academic_status' => 'academic status',
            'admission_year' => 'admission year',
        ];
    }
}
