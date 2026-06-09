<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUlids, HasRoles, HasPermissions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['remember_token', 'password']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'email',
        'password',
        'auth_provider',
        'origin_data'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'origin_data' => 'array'
        ];
    }


    public function person()
    {
        return $this->hasOne(Person::class, 'user_id');
    }


    // public function ownedProject()
    // {
    //     return $this->hasOne(Project::class, 'owner_id');
    // }

    // public function projects()
    // {
    //     return $this->belongsToMany(Project::class, 'user_projects');
    // }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    // public function project()
    // {
    //     return $this->hasOneThrough(Project::class, UserProject::class, 'user_id', 'id', 'id', 'project_id');
    //     // return $this->belongsToMany(Project::class, 'user_projects')->limit(1);
    // }

    /**
     * Get navigation for the current context (Organization).
     */
    public function getNavigationAttribute()
    {
        // This relies on the organization being resolved in the middleware/session
        if (session()->has('organization_id')) {
            $org = \App\Models\Organization::find(session('organization_id'));
            if ($org) {
                return (new \App\Services\NavigationService())->getNavigation($this, $org);
            }
        }
        return [];
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'model_has_permissions', 'model_id', 'permission_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
