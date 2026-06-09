<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantResolver
{
    public function resolve(Request $request): TenantContext
    {
        $user = $request->user();
        $jwtOrgId = $request->attributes->get('jwt_org_id');
        $jwtRole = $request->attributes->get('jwt_role');
        $headerOverride = $request->attributes->get('jwt_org_id_override')
            ?? $request->header('X-Organization-ID')
            ?? $request->header('X-Tenant-ID');

        $organizationId = $headerOverride ?: $jwtOrgId ?: $this->resolveFromSubdomain($request);

        if (! $organizationId) {
            throw new BadRequestHttpException('Organization context is missing. Provide X-Organization-ID or use a tenant subdomain.');
        }

        $organization = Organization::query()->find($organizationId);

        if (! $organization) {
            throw new NotFoundHttpException('Organization not found.');
        }

        if ($user) {
            $membership = $this->membership($user, $organization->id);

            if (! $membership) {
                throw new AccessDeniedHttpException('You do not have access to this organization.');
            }

            $jwtRole = $membership->pivot->role;
        }

        $branch = null;
        $branchId = $request->header('X-Branch-ID');

        if ($branchId) {
            $branch = Branch::withoutGlobalScopes()
                ->where('id', $branchId)
                ->where('organization_id', $organization->id)
                ->first();

            if (! $branch) {
                throw new NotFoundHttpException('Branch does not belong to this organization.');
            }
        }

        return new TenantContext($organization, $branch, $jwtRole);
    }

    private function membership(User $user, string $organizationId): ?Organization
    {
        return $user->organizations()
            ->where('organizations.id', $organizationId)
            ->first();
    }

    private function resolveFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);

        if (! $baseDomain || ! str_ends_with($host, $baseDomain) || $host === $baseDomain) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($baseDomain) - 1);

        if (in_array($subdomain, ['www', 'api', 'admin', 'app', 'staging'], true)) {
            return null;
        }

        return Organization::query()->where('slug', $subdomain)->value('id');
    }
}
