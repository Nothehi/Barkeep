<?php

namespace Modules\Identity\Presentation\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\Commands\LogoutUser;
use Modules\Identity\Application\Commands\UpdateUserProfile;
use Modules\Identity\Presentation\Http\Requests\ProfileDeleteRequest;
use Modules\Identity\Presentation\Http\Requests\ProfileUpdateRequest;

/**
 * Profile basics only.
 *
 * Avatars, preferences, public creator profiles and workspace profiles belong
 * to other contexts and are deliberately not handled here.
 */
class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, UpdateUserProfile $updateUserProfile): RedirectResponse
    {
        $updateUserProfile->handle($request->user(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request, LogoutUser $logoutUser): RedirectResponse
    {
        $user = $request->user();

        $logoutUser->handle($request->session());

        $user->delete();

        return redirect('/');
    }
}
