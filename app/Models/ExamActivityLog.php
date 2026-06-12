<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamActivityLog extends Model
{
    public const EVENT_STARTED = 'started';
    public const EVENT_RESUMED = 'resumed';
    public const EVENT_ANSWERS_SAVED = 'answers_saved';
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_EXPIRED = 'expired';

    protected $fillable = [
        'exam_session_id',
        'student_profile_id',
        'event',
        'context',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }
}
