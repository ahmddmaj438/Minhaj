<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionBankSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bank_question_id' => [
                'required',
                'integer',
                Rule::exists('instructor_exam_questions', 'id')->where('save_to_bank', true),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_question_id' => 'question bank item',
        ];
    }
}
