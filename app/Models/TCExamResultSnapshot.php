<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TCExamResultSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'tcexam_test_link_id',
        'tcexam_test_id',
        'tcexam_testuser_id',
        'tcexam_result_id',
        'score',
        'max_score',
        'percentage',
        'passed',
        'started_at',
        'completed_at',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function testLink(): BelongsTo
    {
        return $this->belongsTo(TCExamTestLink::class, 'tcexam_test_link_id');
    }
}
