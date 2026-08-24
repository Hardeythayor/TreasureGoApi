<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTier;
use App\Models\SubscriptionTierTransaction;
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

        $existing = UserTierSubscription::where('user_id', $request->user()->id)
            ->where('subscription_tier_id', $tier->id)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existing && $existing->status === 'active') {
            return response()->json([
                'message' => 'You are already subscribed to this tier.',
                'subscription' => $existing->load('subscriptionTier'),
            ]);
        }

        [$subscription, $transaction] = DB::transaction(function () use ($request, $tier, $existing) {
            UserTierSubscription::where('user_id', $request->user()->id)
                ->where('is_current', 'yes')
                ->update(['is_current' => 'no']);

            if ($existing) {
                $subscription = tap($existing)->update(['is_current' => 'yes']);
            } else {
                $subscription = UserTierSubscription::create([
                    'user_id' => $request->user()->id,
                    'subscription_tier_id' => $tier->id,
                ]);
            }

            if ($tier->type === 'free') {
                $subscription->update([
                    'status' => 'active',
                    'subscribed_on' => now(),
                ]);

                return [$subscription, null];
            }

            $transaction = SubscriptionTierTransaction::create([
                'subscription_id' => $subscription->id,
                'transaction_reference' => SubscriptionTierTransaction::generateReference(),
                'amount' => $tier->amount,
            ]);

            return [$subscription, $transaction];
        });

        return response()->json([
            'subscription' => $subscription->load('subscriptionTier'),
            'transaction' => $transaction,
        ], $existing ? 200 : 201);
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
