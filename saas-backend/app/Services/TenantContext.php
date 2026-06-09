<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;

class TenantContext
{
    public function __construct(
        public readonly Organization $organization,
        public readonly ?Branch $branch,
        public readonly ?string $role,
    ) {
    }

    public function organizationId(): string
    {
        return $this->organization->id;
    }

    public function branchId(): ?string
    {
        return $this->branch?->id;
    }
}
