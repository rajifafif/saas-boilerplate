<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Organization extends Model
{
    use HasUlids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function configurations()
    {
        return $this->morphMany(Configuration::class, 'configurable');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function navigation()
    {
        return $this->morphOne(Navigation::class, 'navigateable');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    // SaaS Scope: The subscription THIS organization pays for
    public function subscription()
    {
        return $this->hasOne(OrganizationSubscription::class)->latestOfMany();
    }

    // SaaS Scope: Transactions THIS organization made (paying the Platform)
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'payer');
    }

    // Tenant Scope: Transactions RECEIVED by this organization.
    public function incomeTransactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
