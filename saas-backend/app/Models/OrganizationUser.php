<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    protected $table = 'organization_users';

    protected $fillable = [
        'user_id',
        'organization_id',
        'role',
        'is_default',
        'joined_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'joined_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that belongs to this organization.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to get memberships with admin role.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }


    /**
     * Scope to get memberships with member role.
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    /**
     * Check if this membership has admin privileges.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner']);
    }


    /**
     * Check if this membership is a regular member.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }
}
