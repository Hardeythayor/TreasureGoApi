<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treasure;
use App\Models\TreasureHunt;
use App\Models\User;
use App\Notifications\TreasureRewardNotification;
use App\Services\MessageBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class RewardController extends Controller
{
    public function send(Request $request, MessageBroadcastService $broadcaster)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'treasure_id' => ['required', 'integer', 'exists:treasures,id'],
            'amazon_link' => ['required', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($request->user_id);
        $treasure = Treasure::with('subscriptionTier')->findOrFail($request->treasure_id);

        $hunt = TreasureHunt::where('user_id', $user->id)
            ->where('treasure_id', $treasure->id)
            ->first();

        if (! $hunt) {
            return response()->json(['message' => 'This user has not started hunting this treasure.'], 404);
        }

        if ($hunt->status !== 'found') {
            return response()->json(['message' => 'This user has not found this treasure yet.'], 422);
        }

        if ($hunt->reward_status === 'rewarded') {
            return response()->json(['message' => 'This treasure has already been rewarded.'], 422);
        }

        $hunt->update([
            'reward' => $request->amazon_link,
            'reward_status' => 'rewarded',
            'rewarded_at' => now(),
        ]);

        $user->notify(new TreasureRewardNotification($user, $treasure, $request->amazon_link));

        $broadcaster->send(
            type: 'user',
            messageType: 'reward_delivered',
            title: 'Reward delivered',
            message: "Your \${$treasure->subscriptionTier->reward_amount} gift card for {$treasure->name} has been sent to your email.",
            link: config('app.client_url').'/messages',
            recipientIds: collect([$user->id]),
        );

        return response()->json([
            'message' => 'Reward sent successfully.',
            'treasure_hunt' => $hunt->fresh(),
        ]);
    }
}
