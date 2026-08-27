<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filters = $validator->validated();

        $query = User::query()->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'));

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return response()->json([
            'users' => $query->latest()->paginate(50)->through(fn (User $user) => $this->formatUser($user)),
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'sometimes', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'country' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot change the status of your own account.'], 422);
        }

        $user->forceFill([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ])->save();

        if ($user->status === 'inactive') {
            $user->tokens()->delete();
        }

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::password(12);

        $user->update(['password' => Hash::make($newPassword)]);

        $user->tokens()->delete();

        $user->notify(new AdminPasswordResetNotification($newPassword));

        return response()->json(['message' => 'Password reset. The user has been emailed their new password.']);
    }

    private function formatUser(User $user): array
    {
        return array_merge($user->toArray(), [
            'roles' => $user->getRoleNames(),
        ]);
    }
}
