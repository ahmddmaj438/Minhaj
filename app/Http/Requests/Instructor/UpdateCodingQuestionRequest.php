<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCodingQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'starter_code' => ['nullable', 'string', 'max:30000'],
            'expected_output' => ['nullable', 'string', 'max:10000'],
            'constraints' => ['nullable', 'string', 'max:10000'],
            'sample_input' => ['nullable', 'string', 'max:10000'],
            'sample_output' => ['nullable', 'string', 'max:10000'],
            'test_case_notes' => ['nullable', 'string', 'max:10000'],
            'marks' => ['required', 'numeric', 'min:0.25', 'max:9999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard,advanced'],
            'topic' => ['nullable', 'string', 'max:255'],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'problem statement',
            'starter_code' => 'starter code',
            'expected_output' => 'expected output',
            'sample_input' => 'sample input',
            'sample_output' => 'sample output',
            'test_case_notes' => 'test case notes',
            'save_to_bank' => 'save to question bank',
        ];
    }
}
