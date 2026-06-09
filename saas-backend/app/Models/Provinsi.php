<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provinsi extends Model
{
     use HasFactory;

    protected $fillable = [
        'id',
        'country_id',
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function kotas()
    {
        return $this->hasMany(Kota::class, 'provinsi_id', 'id');
    }

}
