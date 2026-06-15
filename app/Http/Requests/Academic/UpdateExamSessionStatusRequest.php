<?php

namespace App\Http\Requests\Academic;

use App\Models\ExamSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamSessionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('db.exam_sessions.update') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                ExamSession::STATUS_IN_PROGRESS,
                ExamSession::STATUS_SUBMITTED,
                ExamSession::STATUS_EXPIRED,
                ExamSession::STATUS_CANCELLED,
            ])],
        ];
    }
}
