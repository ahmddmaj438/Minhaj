<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['required', 'integer', 'exists:instructor_exam_questions,id'],
            'questions.*.position' => ['required', 'integer', 'min:1', 'max:500'],
            'questions.*.marks' => ['required', 'numeric', 'min:0.25', 'max:9999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'questions.*.position' => 'position',
            'questions.*.marks' => 'marks',
        ];
    }
}
