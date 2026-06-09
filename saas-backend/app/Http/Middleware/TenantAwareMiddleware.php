<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAwareMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-ORGANIZATION-ID') ?? $request->header('X-TENANT-ID');

        if (!$organizationId) {
            return response()->json(['error' => 'X-ORGANIZATION-ID header is missing'], 400);
        }

        $organization = Organization::find($organizationId);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        app()->instance('organization_id', $organization->id);
        app()->instance('currentOrganization', $organization);

        return $next($request);
    }
}
