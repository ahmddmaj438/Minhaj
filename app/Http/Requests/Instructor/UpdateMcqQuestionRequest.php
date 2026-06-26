<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionDisplayOverrideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMcqQuestionRequest extends FormRequest
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
            'allow_multiple_correct' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'save_to_bank' => ['nullable', 'boolean'],
            'intent' => ['required', 'string', 'in:save,save_add_another'],
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*.text' => ['nullable', 'string', 'max:3000'],
            'options.*.feedback' => ['nullable', 'string', 'max:1000'],
            'correct_options' => ['nullable', 'array'],
            'correct_options.*' => ['integer', 'min:0', 'max:7'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $options = collect($this->input('options', []))
                    ->map(fn (array $option, int $index) => [
                        'index' => $index,
                        'text' => trim((string) ($option['text'] ?? '')),
                    ])
                    ->filter(fn (array $option) => $option['text'] !== '')
                    ->values();

                if ($options->count() < 2) {
                    $validator->errors()->add('options', 'Add at least two answer options.');
                }

                if ($options->count() !== $options->pluck('text')->map(fn (string $text): string => mb_strtolower($text))->unique()->count()) {
                    $validator->errors()->add('options', 'Answer options cannot be duplicated.');
                }

                $validOptionIndexes = $options->pluck('index')->map(fn ($index) => (string) $index)->all();
                $correctIndexes = collect($this->input('correct_options', []))
                    ->map(fn ($index) => (string) $index)
                    ->intersect($validOptionIndexes);

                if ($correctIndexes->isEmpty()) {
                    $validator->errors()->add('correct_options', 'Select at least one correct answer.');
                }

                if (! $this->boolean('allow_multiple_correct') && $correctIndexes->count() > 1) {
                    $validator->errors()->add('correct_options', 'Enable multiple correct answers before selecting more than one option.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'question prompt',
            'correct_options' => 'correct answer',
            'allow_multiple_correct' => 'multiple correct answers',
            'shuffle_options' => 'shuffle options',
            'save_to_bank' => 'save to question bank',
        ];
    }
}
