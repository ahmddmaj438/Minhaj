<?php

namespace App\Http\Requests\Instructor;

use App\Support\Exams\QuestionDisplayOverrideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacketTracerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scenario' => ['required', 'string', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'expected_tasks' => ['required', 'string', 'max:10000'],
            'configuration_notes' => ['nullable', 'string', 'max:10000'],
            'pkt_file' => ['nullable', 'file', 'max:51200'],
            'topology_screenshot' => ['nullable', 'image', 'max:10240'],
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
            function ($validator): void {
                $file = $this->file('pkt_file');

                if ($file && strtolower($file->getClientOriginalExtension()) !== 'pkt') {
                    $validator->errors()->add('pkt_file', 'The Packet Tracer file must use the .pkt extension.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'pkt_file' => 'Packet Tracer file',
            'topology_screenshot' => 'topology screenshot',
            'expected_tasks' => 'expected tasks',
            'configuration_notes' => 'configuration notes',
            'save_to_bank' => 'save to question bank',
        ];
    }
}
