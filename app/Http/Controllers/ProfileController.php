<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $avatarFile = $request->file('avatar_file');

        if (!$user->isSuperAdmin()) {
            unset($validated['name']);
        }

        unset($validated['avatar_file'], $validated['remove_avatar']);

        if ($avatarFile) {
            $this->deleteManagedAvatar($user->avatar);
            $validated['avatar'] = Storage::disk('public')->url(
                $avatarFile->store('profile-avatars', 'public')
            );
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteManagedAvatar($user->avatar);
            $validated['avatar'] = null;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    private function deleteManagedAvatar(?string $avatar): void
    {
        if (!is_string($avatar) || !str_starts_with($avatar, '/storage/profile-avatars/')) {
            return;
        }

        $path = ltrim(substr($avatar, strlen('/storage/')), '/');
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403, 'Penghapusan akun mandiri dinonaktifkan.');
    }
}
