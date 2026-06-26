<?php

namespace App\Models;

use App\Models\Exam\InstructorExam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAssignment extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'instructor_exam_id',
        'course_id',
        'student_profile_id',
        'assigned_by',
        'available_at',
        'due_at',
        'max_attempts',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'due_at' => 'datetime',
            'max_attempts' => 'integer',
            'settings' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(InstructorExam::class, 'instructor_exam_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function isCourseWide(): bool
    {
        return $this->student_profile_id === null;
    }
}
