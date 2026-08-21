<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionTierController extends Controller
{
    public function active(Request $request)
    {
        return response()->json([
            'subscription_tiers' => SubscriptionTier::where('status', 'active')
                ->select(['id', 'name', 'amount', 'validity', 'type', 'reward_amount'])
                ->with(['currentUserSubscription' => function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id)
                        // ->where('is_current', 'yes')
                        ->where('status', 'active');
                }])
                ->latest()
                ->get(),
        ]);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'search' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:free,premium'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $validator->validated();

        $query = SubscriptionTier::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return response()->json([
            'subscription_tiers' => $query->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reward_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'validity' => ['required', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'in:free,premium'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscriptionTier = SubscriptionTier::create($validator->validated());

        return response()->json([
            'subscription_tier' => $subscriptionTier,
        ], 201);
    }

    public function show(SubscriptionTier $subscriptionTier)
    {
        return response()->json([
            'subscription_tier' => $subscriptionTier,
        ]);
    }

    public function update(Request $request, SubscriptionTier $subscriptionTier)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reward_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'validity' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'in:free,premium'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscriptionTier->update($validator->validated());

        return response()->json([
            'subscription_tier' => $subscriptionTier,
        ]);
    }

    public function destroy(SubscriptionTier $subscriptionTier)
    {
        $subscriptionTier->delete();

        return response()->json(['message' => 'Subscription tier deleted successfully.']);
    }

    public function toggleStatus(SubscriptionTier $subscriptionTier)
    {
        $subscriptionTier->update([
            'status' => $subscriptionTier->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'subscription_tier' => $subscriptionTier,
        ]);
    }
}
