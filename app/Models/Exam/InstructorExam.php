<?php

namespace App\Models\Exam;

use App\Models\Course;
use App\Models\ExamAssignment;
use App\Models\User;
use App\Support\Exams\ExamDisplayFormatCatalog;
use Database\Factories\InstructorExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class InstructorExam extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const FORMAT_ONE_QUESTION_AT_TIME = 'one_question_at_time';
    public const FORMAT_ALL_QUESTIONS = 'all_questions';
    public const FORMAT_GOOGLE_FORMS = 'google_forms';

    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'description',
        'duration_minutes',
        'starts_at',
        'ends_at',
        'total_marks',
        'display_format',
        'status',
        'published_at',
        'tcexam_test_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'total_marks' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InstructorExamQuestion::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\ExamSession::class,
            ExamAssignment::class,
            'instructor_exam_id',
            'exam_assignment_id'
        );
    }

    public function displayFormatKey(): string
    {
        return ExamDisplayFormatCatalog::normalize($this->display_format);
    }

    public function displayFormatMeta(): array
    {
        return ExamDisplayFormatCatalog::find($this->display_format);
    }

    protected static function newFactory(): InstructorExamFactory
    {
        return InstructorExamFactory::new();
    }
}
