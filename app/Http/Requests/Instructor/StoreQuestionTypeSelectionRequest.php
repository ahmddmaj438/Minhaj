<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionTypeCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionTypeSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question_type' => ['required', 'string', Rule::in(QuestionTypeCatalog::keys())],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_type' => 'question type',
        ];
    }
}
