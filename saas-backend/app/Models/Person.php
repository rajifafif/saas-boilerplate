<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Person extends Model implements HasMedia
{
    use HasUlids, InteractsWithMedia;

    protected $table = 'persons';

    protected $fillable = [
        'id',
        'user_id',
        'name_prefix',
        'name',
        'name_suffix',
        'gender',
        'birth_date',
        'birth_place',
        'default_address_id',
        'email',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public $appends = [
        'full_name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function defaultAddress()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function getFullNameAttribute()
    {
        $namePrefix = $this->name_prefix ? $this->name_prefix . ' ' : '';
        $nameSuffix = $this->name_suffix ? ' ' . $this->name_suffix : '';
        return $namePrefix . $this->name . $nameSuffix;
    }

}
