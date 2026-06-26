<?php

namespace App\Models;

use App\Models\Exam\InstructorExam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function instructorExams(): HasMany
    {
        return $this->hasMany(InstructorExam::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class)
            ->withPivot(['is_required', 'recommended_level'])
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'course_student')
            ->withPivot(['enrollment_status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function examAssignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('role', 'teacher')
            ->withPivot(['role', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }
}
