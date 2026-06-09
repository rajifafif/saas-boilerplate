<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'configurable_type',
        'configurable_id',
        'key',
        'value',
    ];

    public function configurable()
    {
        return $this->morphTo();
    }
}
