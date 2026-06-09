<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'organization_id',
        'branch_id', // Optional: if we ever want branch-specific roles, but primarily Org-owned
        'name',
        'guard_name',
    ];

    protected $hidden = [
        'guard_name',
        'created_at',
        'updated_at',
        'pivot'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
