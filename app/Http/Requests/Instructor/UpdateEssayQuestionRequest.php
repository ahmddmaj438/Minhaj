<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionDisplayOverrideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEssayQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'expected_answer' => ['nullable', 'string', 'max:10000'],
            'rubric' => ['nullable', 'string', 'max:10000'],
            'key_points' => ['nullable', 'string', 'max:10000'],
            'mark_distribution' => ['nullable', 'string', 'max:5000'],
            'common_mistakes' => ['nullable', 'string', 'max:5000'],
            'evaluation_instructions' => ['nullable', 'string', 'max:5000'],
            'min_words' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'max_words' => ['nullable', 'integer', 'min:1', 'max:10000', 'gte:min_words'],
            'marks' => ['required', 'numeric', 'min:0.25', 'max:9999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard,advanced'],
            'topic' => ['nullable', 'string', 'max:255'],
            'display_override' => ['required', Rule::in(QuestionDisplayOverrideCatalog::keys())],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'question prompt',
            'expected_answer' => 'model answer',
            'key_points' => 'key points',
            'mark_distribution' => 'mark distribution',
            'common_mistakes' => 'common mistakes',
            'evaluation_instructions' => 'evaluation instructions',
            'min_words' => 'minimum words',
            'max_words' => 'maximum words',
            'save_to_bank' => 'save to question bank',
        ];
    }
}
