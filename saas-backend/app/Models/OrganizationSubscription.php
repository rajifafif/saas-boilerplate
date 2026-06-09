<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'subscription_plan_id',
        'start_at',
        'end_at',
        'status',
        'stripe_id', // Fallback for international or future-proofing
        'midtrans_order_id'
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('end_at', '>', now()->timestamp);
    }
}
