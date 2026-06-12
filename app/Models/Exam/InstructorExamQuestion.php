<?php

namespace App\Models\Exam;

use App\Models\ExamSessionAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorExamQuestion extends Model
{
    protected $fillable = [
        'instructor_exam_id',
        'type',
        'category',
        'title',
        'position',
        'marks',
        'difficulty',
        'topic',
        'programming_language',
        'display_override',
        'save_to_bank',
        'tcexam_question_id',
        'tcexam_subject_id',
        'prompt',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'marks' => 'decimal:2',
            'save_to_bank' => 'boolean',
            'prompt' => 'array',
            'settings' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(InstructorExam::class, 'instructor_exam_id');
    }

    public function sessionAnswers(): HasMany
    {
        return $this->hasMany(ExamSessionAnswer::class);
    }
}
