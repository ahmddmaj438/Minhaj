<?php

namespace App\Http\Requests\Academic;

use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.student_profiles.insert') === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:student_profiles,user_id'],
            'student_name' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
            'student_email' => ['required_without:user_id', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'student_password' => ['required_without:user_id', 'nullable', Password::defaults()],
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
            'student_name' => 'student name',
            'student_email' => 'student email',
            'student_password' => 'temporary password',
            'major_id' => 'program',
            'student_number' => 'student number',
            'academic_status' => 'academic status',
            'admission_year' => 'admission year',
        ];
    }
}
