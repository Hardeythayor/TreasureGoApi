<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageRecipient;
use Illuminate\Http\Request;

class MessageRecipientController extends Controller
{
    public function index(Request $request)
    {
        $notifications = MessageRecipient::where('receiver_id', $request->user()->id)
            ->where('is_deleted', 'no')
            ->with('message')
            ->latest()
            ->paginate(30);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, MessageRecipient $notification)
    {
        if ($notification->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'This notification does not belong to you.'], 403);
        }

        $notification->update(['is_read' => '1']);

        return response()->json([
            'notification' => $notification->fresh('message'),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        MessageRecipient::where('receiver_id', $request->user()->id)
            ->where('is_deleted', 'no')
            ->where('is_read', '0')
            ->update(['is_read' => '1']);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, MessageRecipient $notification)
    {
        if ($notification->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'This notification does not belong to you.'], 403);
        }

        $notification->update(['is_deleted' => 'yes']);

        return response()->json(['message' => 'Notification removed.']);
    }

    public function deleteAll(Request $request)
    {
        MessageRecipient::where('receiver_id', $request->user()->id)
            ->where('is_deleted', 'no')
            ->update(['is_deleted' => 'yes']);

        return response()->json(['message' => 'All notifications removed.']);
    }
}
