<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'default_icon',
        'fe_path',
        'permission_id'
    ];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function navigation()
    {
        return $this->morphOne(Navigation::class, 'navigateable');
    }
}
