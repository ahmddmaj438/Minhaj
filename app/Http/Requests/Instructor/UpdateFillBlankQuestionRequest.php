<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFillBlankQuestionRequest extends FormRequest
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
            'case_sensitive' => ['nullable', 'boolean'],
            'trim_whitespace' => ['nullable', 'boolean'],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
            'blanks' => ['required', 'array', 'min:1', 'max:12'],
            'blanks.*.label' => ['nullable', 'string', 'max:255'],
            'blanks.*.answers' => ['nullable', 'string', 'max:2000'],
            'blanks.*.hint' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $blanks = collect($this->input('blanks', []))
                    ->map(fn (array $blank) => [
                        'label' => trim((string) ($blank['label'] ?? '')),
                        'answers' => trim((string) ($blank['answers'] ?? '')),
                    ])
                    ->filter(fn (array $blank) => $blank['label'] !== '' || $blank['answers'] !== '');

                if ($blanks->isEmpty()) {
                    $validator->errors()->add('blanks', 'Add at least one blank and its accepted answer.');
                }

                foreach ($blanks as $index => $blank) {
                    if ($blank['answers'] === '') {
                        $validator->errors()->add("blanks.$index.answers", 'Each blank needs at least one accepted answer.');
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'question passage',
            'case_sensitive' => 'case sensitive answers',
            'trim_whitespace' => 'ignore extra spaces',
            'save_to_bank' => 'save to question bank',
            'blanks.*.label' => 'blank label',
            'blanks.*.answers' => 'accepted answers',
        ];
    }
}
