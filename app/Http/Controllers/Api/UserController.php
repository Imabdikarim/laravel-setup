<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->present($user, $request->user()))
            ->values();

        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'] ?? true,
        ])->save();

        return response()->json(['data' => $this->present($user, $request->user())], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::min(8)],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $isSelf = $user->is($request->user());
        $isAdmin = (bool) ($data['is_admin'] ?? $user->is_admin);

        // The person making the change is always an admin, so blocking this
        // guarantees at least one admin can always sign in.
        if ($isSelf && ! $isAdmin) {
            throw ValidationException::withMessages([
                'is_admin' => 'You can’t remove your own admin access.',
            ]);
        }

        $attributes = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'is_admin' => $isAdmin,
        ];

        $passwordChanged = ! empty($data['password']);

        if ($passwordChanged) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user->forceFill($attributes)->save();

        // Sign the other person out when their password changes or access is removed.
        if (! $isSelf && ($passwordChanged || ! $isAdmin)) {
            $this->endSessions($user);
        }

        return response()->json(['data' => $this->present($user, $request->user())]);
    }

    public function destroy(Request $request, User $user): JsonResponse|Response
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'You can’t delete your own account.'], 422);
        }

        $this->endSessions($user);
        $user->delete();

        return response()->noContent();
    }

    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }

    private function present(User $user, User $viewer): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'is_you' => $user->is($viewer),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
