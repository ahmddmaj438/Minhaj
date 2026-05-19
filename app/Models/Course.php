<?php

namespace App\Models;

use App\Models\Exam\InstructorExam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
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
}
