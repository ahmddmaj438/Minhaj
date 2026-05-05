<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TCExamTestLink extends Model
{
    protected $fillable = [
        'tcexam_test_id',
        'title',
        'context_type',
        'context_id',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function resultSnapshots(): HasMany
    {
        return $this->hasMany(TCExamResultSnapshot::class);
    }
}
