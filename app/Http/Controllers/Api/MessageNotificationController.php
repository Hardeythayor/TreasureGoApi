<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageNotification;
use App\Http\Controllers\Controller;
use App\Models\MessageNotification;
use App\Models\MessageRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageNotificationController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:all,tier,user'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'link' => ['required', 'string'],
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

        if ($recipientIds->isEmpty()) {
            return response()->json(['message' => 'No matching recipients found.'], 422);
        }

        $notification = DB::transaction(function () use ($request, $recipientIds) {
            $notification = MessageNotification::create([
                'sender_id' => $request->user()->id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'recipients' => $recipientIds->values()->all(),
                'recipient_count' => $recipientIds->count(),
                'link' => $request->link,
            ]);

            $rows = $recipientIds->map(fn ($id) => [
                'message_id' => $notification->id,
                'receiver_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            MessageRecipient::insert($rows);

            return $notification;
        });

        try {
            broadcast(new NewMessageNotification($notification, $recipientIds->values()->all()));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'notification' => $notification,
        ], 201);
    }
}
