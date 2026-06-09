<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $context = app(TenantResolver::class)->resolve($request);
        } catch (BadRequestHttpException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error' => 'organization_context_missing'], 400);
        } catch (AccessDeniedHttpException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error' => 'organization_access_denied'], 403);
        } catch (NotFoundHttpException $exception) {
            $error = str_contains($exception->getMessage(), 'Branch') ? 'branch_not_found' : 'organization_not_found';

            return response()->json(['message' => $exception->getMessage(), 'error' => $error], 404);
        }

        $request->attributes->set('organization_id', $context->organizationId());
        $request->attributes->set('organization_role', $context->role);
        $request->attributes->set('currentOrganization', $context->organization);

        app()->instance('organization_id', $context->organizationId());
        app()->instance('organization_role', $context->role);
        app()->instance('currentOrganization', $context->organization);

        if ($context->branch) {
            $request->attributes->set('branch_id', $context->branchId());
            app()->instance('branch_id', $context->branchId());
        }

        if (session()->isStarted()) {
            session(['organization_id' => $context->organizationId(), 'organization_role' => $context->role]);

            if ($context->branch) {
                session(['branch_id' => $context->branchId()]);
            }
        }

        return $next($request);
    }
}
