<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treasure;
use App\Models\TreasureHunt;
use App\Models\User;
use App\Services\MessageBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TreasureHuntController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'search' => ['sometimes', 'string', 'max:255'],
            'subscription_tier_id' => ['sometimes', 'integer', 'exists:subscription_tiers,id'],
            'status' => ['sometimes', 'string', 'in:pending,rewarded'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $validator->validated();

        $query = TreasureHunt::with(['user', 'treasure'])->where('status', 'found');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })->orWhereHas('treasure', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            });
        }

        if (! empty($filters['subscription_tier_id'])) {
            $tierId = $filters['subscription_tier_id'];
            $query->whereHas('treasure', function ($query) use ($tierId) {
                $query->where('subscription_tier_id', $tierId);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('reward_status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('found_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('found_at', '<=', $filters['to_date']);
        }

        return response()->json([
            'treasure_hunts' => $query->latest('found_at')->paginate(30),
        ]);
    }

    public function mine(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'status' => ['sometimes', 'string', 'in:hidden,found'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = TreasureHunt::where('user_id', $request->user()->id)->with('treasure.subscriptionTier');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'treasure_hunts' => $query->latest()->paginate(50),
        ]);
    }

    public function analytics()
    {
        return response()->json([
            'total_found' => TreasureHunt::where('status', 'found')->count(),
            'total_rewarded' => TreasureHunt::where('status', 'found')->where('reward_status', 'rewarded')->count(),
            'total_pending' => TreasureHunt::where('status', 'found')->where('reward_status', 'pending')->count(),
        ]);
    }

    public function start(Request $request, Treasure $treasure)
    {
        $hunt = TreasureHunt::updateOrCreate([
            'user_id' => $request->user()->id,
            'treasure_id' => $treasure->id,
        ]);

        return response()->json([
            'treasure_hunt' => $hunt->load('treasure'),
        ], $hunt->wasRecentlyCreated ? 201 : 200);
    }

    public function find(Request $request, Treasure $treasure, MessageBroadcastService $broadcaster)
    {
        $hunt = TreasureHunt::where('user_id', $request->user()->id)
            ->where('treasure_id', $treasure->id)
            ->first();

        if (! $hunt) {
            return response()->json(['message' => 'You have not started hunting this treasure yet.'], 404);
        }

        if ($hunt->status === 'found') {
            return response()->json(['message' => 'You have already found this treasure.'], 422);
        }

        if ($treasure->status === 'found') {
            return response()->json(['message' => 'This treasure has already been found.'], 422);
        }

        DB::transaction(function () use ($hunt, $treasure) {
            $hunt->update([
                'status' => 'found',
                'found_at' => now(),
            ]);

            $treasure->update(['status' => 'found']);
        });

        $broadcaster->send(
            type: 'user',
            messageType: 'treasure_found_self',
            title: 'You found it! 🏆',
            message: "Congratulations on finding the {$treasure->name}. Reward instructions are on the way.",
            link: config('app.client_url')."/treasures/{$treasure->id}",
            recipientIds: collect([$request->user()->id]),
        );

        $otherSubscriberIds = User::whereHas('subscriptions', function ($query) use ($treasure) {
            $query->where('subscription_tier_id', $treasure->subscription_tier_id)
                ->where('is_current', 'yes')
                ->where('status', 'active');
        })->where('id', '!=', $request->user()->id)->pluck('id');

        $broadcaster->send(
            type: 'tier',
            messageType: 'treasure_found',
            title: 'Treasure Found',
            message: "{$treasure->name} has just been found by another hunter. Keep searching for the next one!",
            link: config('app.client_url')."/treasures/{$treasure->id}",
            recipientIds: $otherSubscriberIds,
        );

        $adminIds = User::whereHas('roles', fn ($query) => $query->where('name', 'admin'))->pluck('id');

        $broadcaster->send(
            type: 'admins',
            messageType: 'treasure_found_alert',
            title: 'Treasure Found',
            message: "{$treasure->name} was found by {$request->user()->name}.",
            link: config('app.client_url')."/treasures/{$treasure->id}",
            recipientIds: $adminIds,
        );

        return response()->json([
            'treasure_hunt' => $hunt->fresh(),
            'treasure' => $treasure->fresh(),
        ]);
    }
}
