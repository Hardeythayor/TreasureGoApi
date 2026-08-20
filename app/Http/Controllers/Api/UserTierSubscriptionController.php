<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTier;
use App\Models\UserTierSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserTierSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_tier_id' => ['required', 'integer', 'exists:subscription_tiers,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tier = SubscriptionTier::findOrFail($request->subscription_tier_id);

        if ($tier->status !== 'active') {
            return response()->json(['message' => 'This subscription tier is not available.'], 422);
        }

        $subscription = DB::transaction(function () use ($request, $tier) {
            UserTierSubscription::where('user_id', $request->user()->id)
                ->where('is_current', 'yes')
                ->update(['is_current' => 'no']);

            return UserTierSubscription::create([
                'user_id' => $request->user()->id,
                'subscription_tier_id' => $tier->id,
                'subscribed_on' => now(),
            ]);
        });

        return response()->json([
            'subscription' => $subscription->load('subscriptionTier'),
        ], 201);
    }

    public function current(Request $request)
    {
        $subscription = UserTierSubscription::where('user_id', $request->user()->id)
            ->where('is_current', 'yes')
            ->with('subscriptionTier')
            ->first();

        return response()->json([
            'subscription' => $subscription,
        ]);
    }
}
