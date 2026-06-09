<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    use HasUlids;

    public $fillable = [
        'menu'
    ];

    public $casts = [
        'menu' => 'array'
    ];



    public function naviagateable()
    {
        return $this->morphTo();
    }
}
