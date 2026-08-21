<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTier;
use App\Models\Treasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TreasureController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Treasure::latest()->get(),
        ]);
    }

    public function byTier(SubscriptionTier $subscriptionTier)
    {
        return response()->json([
            'treasures' => Treasure::where('subscription_tier_id', $subscriptionTier->id)->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'subscription_tier_id' => ['required', 'integer', 'exists:subscription_tiers,id'],
            'location' => ['required', 'array'],
            'location.lat' => ['required', 'numeric', 'between:-90,90'],
            'location.lng' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'string', 'in:hidden,found'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('treasures', 'public');
        }

        $treasure = Treasure::create($data);

        return response()->json([
            'treasure' => $treasure,
        ], 201);
    }

    public function show(Treasure $treasure)
    {
        return response()->json([
            'treasure' => $treasure,
        ]);
    }

    public function update(Request $request, Treasure $treasure)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'region' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'subscription_tier_id' => ['sometimes', 'integer', 'exists:subscription_tiers,id'],
            'location' => ['sometimes', 'array'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'string', 'in:hidden,found'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('icon')) {
            if ($treasure->icon) {
                Storage::disk('public')->delete($treasure->icon);
            }

            $data['icon'] = $request->file('icon')->store('treasures', 'public');
        }

        $treasure->update($data);

        return response()->json([
            'treasure' => $treasure,
        ]);
    }

    public function destroy(Treasure $treasure)
    {
        if ($treasure->icon) {
            Storage::disk('public')->delete($treasure->icon);
        }

        $treasure->delete();

        return response()->json(['message' => 'Treasure deleted successfully.']);
    }

    public function toggleStatus(Treasure $treasure)
    {
        $treasure->update([
            'status' => $treasure->status === 'hidden' ? 'found' : 'hidden',
        ]);

        return response()->json([
            'treasure' => $treasure,
        ]);
    }
}
