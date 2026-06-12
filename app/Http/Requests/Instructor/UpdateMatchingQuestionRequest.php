<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionDisplayOverrideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMatchingQuestionRequest extends FormRequest
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
            'marks' => ['required', 'numeric', 'min:0.25', 'max:9999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard,advanced'],
            'topic' => ['nullable', 'string', 'max:255'],
            'display_override' => ['required', Rule::in(QuestionDisplayOverrideCatalog::keys())],
            'shuffle_left_items' => ['nullable', 'boolean'],
            'shuffle_right_items' => ['nullable', 'boolean'],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
            'pairs' => ['required', 'array', 'min:2', 'max:12'],
            'pairs.*.left' => ['nullable', 'string', 'max:2000'],
            'pairs.*.right' => ['nullable', 'string', 'max:2000'],
            'pairs.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pairs = collect($this->input('pairs', []))
                    ->map(fn (array $pair) => [
                        'left' => trim((string) ($pair['left'] ?? '')),
                        'right' => trim((string) ($pair['right'] ?? '')),
                    ])
                    ->filter(fn (array $pair) => $pair['left'] !== '' || $pair['right'] !== '');

                if ($pairs->count() < 2) {
                    $validator->errors()->add('pairs', 'Add at least two matching pairs.');
                }

                foreach ($pairs as $index => $pair) {
                    if ($pair['left'] === '' || $pair['right'] === '') {
                        $validator->errors()->add("pairs.$index", 'Each matching pair needs both sides filled in.');
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'question prompt',
            'shuffle_left_items' => 'shuffle left items',
            'shuffle_right_items' => 'shuffle right items',
            'save_to_bank' => 'save to question bank',
            'pairs.*.left' => 'left item',
            'pairs.*.right' => 'matching answer',
        ];
    }
}
