<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactUsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        Mail::to(config('app.contact_email'))->send(new ContactUsMail(
            senderName: $user->name,
            senderEmail: $user->email,
            contactSubject: $request->subject,
            contactMessage: $request->message,
        ));

        return response()->json(['message' => 'Your message has been sent. We will get back to you soon.']);
    }
}
