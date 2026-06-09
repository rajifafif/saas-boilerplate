<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends User
{
    // use SoftDeletes,HasUlids;

    // protected $fillable = [
    //     'user_id',
    //     'name',
    //     'phone',
    //     'birth_date',
    //     'email'
    // ];
}
