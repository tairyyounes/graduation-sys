<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        activity()
            ->causedBy($request->user())
            ->log('User updated their password');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('auth.password_updated') ?? 'Password updated successfully.',
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}

