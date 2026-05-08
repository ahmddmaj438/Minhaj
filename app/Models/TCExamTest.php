<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCExamTest extends Model
{
    protected $table = 'tce_tests';
    protected $primaryKey = 'test_id';

    public $timestamps = false;
    public $incrementing = true;

    protected $guarded = [];
}
