<?php

namespace App\Models;

use App\Models\Exam\InstructorExamQuestion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSessionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'instructor_exam_question_id',
        'answer_payload',
        'score',
        'feedback',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer_payload' => 'array',
            'score' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InstructorExamQuestion::class, 'instructor_exam_question_id');
    }
}
