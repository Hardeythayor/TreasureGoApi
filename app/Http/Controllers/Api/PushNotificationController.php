<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pusher\PushNotifications\PushNotifications;

class PushNotificationController extends Controller
{
    public function getBeamsToken(Request $request)
    {
        $instanceId = config('services.pusher_beams.instance_id');
        $secretKey = config('services.pusher_beams.secret_key');

        if (! $instanceId || ! $secretKey) {
            return response()->json(['message' => 'Push notifications are not configured.'], 503);
        }

        $user = $request->user();

        $user->forceFill(['enable_notification' => 'yes'])->save();

        $beams = new PushNotifications([
            'instanceId' => $instanceId,
            'secretKey' => $secretKey,
        ]);

        $token = $beams->generateToken('user-'.$user->id);

        return response()->json($token);
    }

    public function removeBeamsToken(Request $request)
    {
        $user = $request->user();

        $user->forceFill(['enable_notification' => 'no'])->save();

        $instanceId = config('services.pusher_beams.instance_id');
        $secretKey = config('services.pusher_beams.secret_key');

        if ($instanceId && $secretKey) {
            try {
                (new PushNotifications([
                    'instanceId' => $instanceId,
                    'secretKey' => $secretKey,
                ]))->deleteUser('user-'.$user->id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => 'Push notifications disabled.']);
    }
}
