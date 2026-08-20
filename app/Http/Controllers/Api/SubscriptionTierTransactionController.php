<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTierTransaction;
use App\Models\UserTierSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SubscriptionTierTransactionController extends Controller
{
    public function store(Request $request, UserTierSubscription $subscription)
    {
        if ($subscription->user_id !== $request->user()->id) {
            return response()->json(['message' => 'This subscription does not belong to you.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_reference' => ['required', 'string', 'max:255', 'unique:subscription_tier_transactions,transaction_reference'],
            'payment_type' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = SubscriptionTierTransaction::create([
            'subscription_id' => $subscription->id,
            'transaction_reference' => $request->transaction_reference,
            'payment_type' => $request->payment_type,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'transaction' => $transaction,
        ], 201);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_reference' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction = SubscriptionTierTransaction::where('transaction_reference', $request->transaction_reference)->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if ($transaction->subscription->user_id !== $request->user()->id) {
            return response()->json(['message' => 'This transaction does not belong to you.'], 403);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'This transaction has already been processed.'], 422);
        }

        try {
            $response = Http::withToken(config('services.flutterwave.secret_key'))
                ->get(config('services.flutterwave.base_url').'/transactions/verify_by_reference', [
                    'tx_ref' => $transaction->transaction_reference,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return response()->json(['message' => 'Unable to reach the payment provider. Please try again.'], 502);
        }

        $body = $response->json();

        if ($response->failed() || ($body['status'] ?? null) !== 'success') {
            return response()->json(['message' => 'Unable to verify this transaction with the payment provider.'], 502);
        }

        $data = $body['data'] ?? [];

        $isSuccessful = ($data['status'] ?? null) === 'successful'
            && (float) ($data['amount'] ?? 0) >= (float) $transaction->amount;

        DB::transaction(function () use ($transaction, $isSuccessful, $data, $body) {
            $transaction->update([
                'status' => $isSuccessful ? 'approved' : 'declined',
                'payment_type' => $data['payment_type'] ?? $transaction->payment_type,
                'raw_response' => $body,
            ]);

            if ($isSuccessful) {
                $transaction->subscription()->update(['status' => 'active']);
            }
        });

        return response()->json([
            'transaction' => $transaction->fresh('subscription'),
        ]);
    }
}
