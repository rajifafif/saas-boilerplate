<?php
namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as SpatieTenantFinder;

class TenantFinder extends SpatieTenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $user = Auth::user();

        return !empty($user->default_tenant_id) ? app(IsTenant::class)::whereId($user->default_tenant_id)->first() : null;
    }
}
