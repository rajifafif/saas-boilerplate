<?php

namespace App\Models\Traits;

use App\Models\Organization;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to an organization.
 * 
 * This trait:
 * 1. Automatically applies TenantScope for data isolation
 * 2. Auto-fills organization_id on model creation from session
 * 3. Provides the organization() relationship
 * 
 * Usage: Add `use BelongsToOrganization;` to your model
 */
trait BelongsToOrganization
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToOrganization(): void
    {
        // Apply global scope for tenant isolation
        static::addGlobalScope(new TenantScope);

        // Auto-fill organization_id when creating
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                if (app()->bound('organization_id')) {
                    $model->organization_id = app('organization_id');
                } elseif (session()->has('organization_id')) {
                    $model->organization_id = session('organization_id');
                }
            }
        });
    }

    /**
     * Get the organization that owns this model.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope a query to a specific organization.
     * Useful when you need to bypass the global scope.
     */
    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where($this->getTable() . '.organization_id', $organizationId);
    }
}
