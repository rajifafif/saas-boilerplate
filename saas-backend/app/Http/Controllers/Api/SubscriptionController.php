<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return response()->json(SubscriptionPlan::all());
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'organization_id' => 'required|exists:organizations,id',
        ]);

        $plan = SubscriptionPlan::find($request->plan_id);
        $org = Organization::find($request->organization_id);

        // Logic for creating a sub (Mocking payment success here)
        $start = now()->timestamp;
        $end = now()->addMonths(1)->timestamp;

        $sub = OrganizationSubscription::updateOrCreate(
            ['organization_id' => $org->id],
            [
                'subscription_plan_id' => $plan->id,
                'start_at' => $start,
                'end_at' => $end,
                'status' => 'active'
            ]
        );

        return response()->json([
            'message' => 'Subscribed to ' . $plan->name . ' successfully',
            'expires_at' => date('Y-m-d', $end)
        ]);
    }
}
