<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     * This renders the frontend view where users can update their account details (name, email, password, etc).
     *
     * @param Request $request
     * @return View
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     * Handles the form submission when a user tries to change their name or email address.
     *
     * @param ProfileUpdateRequest $request The validated request containing the new profile details.
     * @return RedirectResponse
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Fill the authenticated user's model with the validated data from the form
        $request->user()->fill($request->validated());

        // If the user changed their email address, we must reset their verification status
        // so they are forced to verify the new email address for security reasons.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Save the updated model to the database
        $request->user()->save();

        // Redirect them back to the edit page with a success flag in the session
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account completely.
     * Handles the destructive action of a user choosing to delete their profile.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        // For security, deleting an account requires the user to confirm their current password
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Log the user out of their session
        Auth::logout();

        // Permanently delete the user record from the database
        $user->delete();

        // Destroy the current session to ensure no stale data or authenticated state remains
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect the guest back to the homepage
        return Redirect::to('/');
    }
}
