<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MessageBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageNotificationController extends Controller
{
    public function send(Request $request, MessageBroadcastService $broadcaster)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:all,tier,user'],
            'message_type' => ['required', 'string', 'in:announcement,congratulatory,system'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'subscription_tier_id' => ['required_if:type,tier', 'integer', 'exists:subscription_tiers,id'],
            'user_id' => ['required_if:type,user', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $recipientIds = match ($request->type) {
            'all' => User::whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))->pluck('id'),
            'tier' => User::whereHas('subscriptions', function ($query) use ($request) {
                $query->where('subscription_tier_id', $request->subscription_tier_id)
                    ->where('is_current', 'yes')
                    ->where('status', 'active');
            })->pluck('id'),
            'user' => collect([(int) $request->user_id]),
        };

        $notification = $broadcaster->send(
            type: $request->type,
            messageType: $request->message_type,
            title: $request->title,
            message: $request->message,
            link: config('app.client_url').'/messages',
            recipientIds: $recipientIds,
            senderId: $request->user()->id,
        );

        if (! $notification) {
            return response()->json(['message' => 'No matching recipients found.'], 422);
        }

        return response()->json([
            'notification' => $notification,
        ], 201);
    }
}
