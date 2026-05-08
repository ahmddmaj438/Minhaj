<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCExamTestUser extends Model
{
    protected $table = 'tce_tests_users';
    protected $primaryKey = 'testuser_id';

    public $timestamps = false;
    public $incrementing = true;

    protected $guarded = [];
}
