<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'save_to_bank',
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
}
