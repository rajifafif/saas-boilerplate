<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Tenant Scope
 *
 * Automatically scopes queries to the current organization.
 * 
 * Reads organization_id from (in order):
 * 1. App container (set by JwtMiddleware/TenantMiddleware - stateless)
 * 2. Session (legacy fallback)
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = $this->getOrganizationId();

        if ($organizationId) {
            $builder->where($model->getTable() . '.organization_id', $organizationId);
        }
    }

    /**
     * Get organization ID from container or session.
     */
    protected function getOrganizationId(): ?string
    {
        // 1. Try app container first (stateless, set by middleware)
        if (app()->bound('organization_id')) {
            return app('organization_id');
        }

        // 2. Try JWT context from app container
        if (app()->bound('jwt.org_id')) {
            return app('jwt.org_id');
        }

        // 3. Fallback to session (legacy)
        if (session()->has('organization_id')) {
            return session()->get('organization_id');
        }

        return null;
    }
}

