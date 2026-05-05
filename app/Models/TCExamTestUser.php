<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCExamTestUser extends Model
{
    protected $connection = 'tcexam';
    protected $table = 'tests_users';
    protected $primaryKey = 'testuser_id';

    public $timestamps = false;
    public $incrementing = true;

    protected $guarded = [];
}
