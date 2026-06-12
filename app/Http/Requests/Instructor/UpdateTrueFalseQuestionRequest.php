<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionDisplayOverrideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTrueFalseQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'statement' => ['required', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'correct_answer' => ['required', 'string', 'in:true,false'],
            'wrong_terms' => ['nullable', 'array', 'max:8'],
            'wrong_terms.*.text' => ['nullable', 'string', 'max:255'],
            'wrong_terms.*.correction' => ['nullable', 'string', 'max:255'],
            'corrected_statement' => ['nullable', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'marks' => ['required', 'numeric', 'min:0.25', 'max:9999.99'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard,advanced'],
            'topic' => ['nullable', 'string', 'max:255'],
            'display_override' => ['required', Rule::in(QuestionDisplayOverrideCatalog::keys())],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $question = $this->route('question');

                if ($question?->type === 'true_false_correct') {
                    $terms = collect($this->input('wrong_terms', []))
                        ->map(fn (array $term) => [
                            'text' => trim((string) ($term['text'] ?? '')),
                            'correction' => trim((string) ($term['correction'] ?? '')),
                        ])
                        ->filter(fn (array $term) => $term['text'] !== '' || $term['correction'] !== '');

                    if ($terms->isEmpty()) {
                        $validator->errors()->add('wrong_terms', 'Add at least one wrong word or phrase and its correction.');
                    }

                    foreach ($terms as $index => $term) {
                        if ($term['text'] === '' || $term['correction'] === '') {
                            $validator->errors()->add("wrong_terms.$index", 'Each wrong word or phrase needs its correction.');
                        }
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'correct_answer' => 'correct answer',
            'wrong_terms' => 'wrong words or phrases',
            'wrong_terms.*.text' => 'wrong word or phrase',
            'wrong_terms.*.correction' => 'correction',
            'corrected_statement' => 'full corrected statement',
            'save_to_bank' => 'save to question bank',
        ];
    }
}
