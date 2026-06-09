<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organization;
use App\Models\Branch;

/**
 * Tenant Middleware (Stateless)
 *
 * Resolves tenant context from JWT claims first, then falls back to:
 * 1. X-Organization-ID header (explicit override)
 * 2. Subdomain parsing (e.g., studioa.movana.id)
 * 
 * Sets request attributes for stateless operation:
 * - organization_id: Current organization ID
 * - organization_role: User's role in the organization
 * - branch_id: Current branch ID (if set)
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Get org context from JWT (set by JwtMiddleware)
        $jwtOrgId = $request->attributes->get('jwt_org_id');
        $jwtRole = $request->attributes->get('jwt_role');
        $headerOverride = $request->attributes->get('jwt_org_id_override');

        // 2. Check if there's a header override
        $effectiveOrgId = $headerOverride ?? $jwtOrgId;

        // 3. If still no org, try subdomain parsing
        if (!$effectiveOrgId) {
            $effectiveOrgId = $this->resolveFromSubdomain($request);
        }

        // 4. If we have an org ID, validate and set context
        if ($effectiveOrgId) {
            // If there's an override, validate user membership
            if ($headerOverride && $user) {
                $membership = $user->organizations()
                    ->where('organizations.id', $effectiveOrgId)
                    ->first();

                if (!$membership) {
                    return response()->json([
                        'message' => 'You do not have access to this organization.',
                        'error' => 'organization_access_denied',
                    ], 403);
                }

                // Use the role from the overridden org
                $jwtRole = $membership->pivot->role;
            }

            // Set request attributes for stateless access
            $request->attributes->set('organization_id', $effectiveOrgId);
            $request->attributes->set('organization_role', $jwtRole);

            // Also set in app container for global access
            app()->instance('organization_id', $effectiveOrgId);
            app()->instance('organization_role', $jwtRole);

            // Legacy session support (for any code still using session)
            // This is temporary and should be removed once all code uses request attributes
            if (session()->isStarted()) {
                session(['organization_id' => $effectiveOrgId]);
                session(['organization_role' => $jwtRole]);
            }
        }

        // Handle branch context
        $branchId = $request->header('X-Branch-ID');
        if ($branchId && $effectiveOrgId) {
            // Validate branch belongs to organization
            $branch = Branch::withoutGlobalScopes()
                ->where('id', $branchId)
                ->where('organization_id', $effectiveOrgId)
                ->first();

            if (!$branch) {
                return response()->json([
                    'message' => 'Branch does not belong to this organization.',
                    'error' => 'branch_not_found',
                ], 404);
            }

            $request->attributes->set('branch_id', $branchId);
            app()->instance('branch_id', $branchId);

            if (session()->isStarted()) {
                session(['branch_id' => $branchId]);
            }
        }

        return $next($request);
    }

    /**
     * Resolve organization ID from subdomain.
     */
    protected function resolveFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $appUrl = config('app.url');
        $baseDomain = parse_url($appUrl, PHP_URL_HOST);

        // Check if host ends with baseDomain and isn't exactly baseDomain
        if ($baseDomain && str_ends_with($host, $baseDomain) && $host !== $baseDomain) {
            $subdomain = substr($host, 0, -strlen($baseDomain) - 1);

            // Skip common non-tenant subdomains
            if (in_array($subdomain, ['www', 'api', 'admin', 'app', 'staging'])) {
                return null;
            }

            // Lookup Org by Slug
            $org = Organization::where('slug', $subdomain)->first();

            if ($org) {
                return $org->id;
            }
        }

        return null;
    }
}


