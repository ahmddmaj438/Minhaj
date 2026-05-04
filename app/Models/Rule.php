<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rule extends Model
{
    protected $fillable = [
        'resource',
        'action',
        'effect',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }
}

