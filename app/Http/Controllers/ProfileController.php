<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class ProfileController extends Controller
{
  /**
   * Display the user's profile form.
   */
  public function edit(Request $request): Response
  {
    return Inertia::render('Profile/Index', [
      'user' => $request->user(),
    ]);
  }

  /**
   * Update the user's profile information.
   */
  public function update(Request $request, User $user): RedirectResponse
  {
    // Rule::unique(User::class)->ignore($this->user()->id),
    $validatedData = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:users,email,' . $user->id,
      'phone_number' => 'nullable|string',
      'gender' => 'nullable|in:male,female',
      'bio' => 'nullable|string|max:500',
      'timezone' => 'nullable|string',
      'locale' => 'nullable|string',
      'avatar' => 'nullable|image|max:2048'
    ]);

    $userData = [
      'name' => $validatedData['name'],
      'phone_number' => $validatedData['phone_number'] ?? null,
      'gender' => $validatedData['gender'] ?? null,
      'bio' => $validatedData['bio'] ?? null,
      'timezone' => $validatedData['timezone'] ?? 'cat',
      'locale' => $validatedData['locale'] ?? 'en',
    ];

    if ($request->user()->isDirty('email')) {
      $userData['email'] = $validatedData['email'];
      $userData['email_verified_at'] = null;
    }

    // Update basic profile information
    $user->update($userData);

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
      try {
        // Clear existing avatar
        $user->clearMediaCollection('avatar');

        // Add new avatar
        $user->addMediaFromRequest('avatar')
          ->toMediaCollection('avatar');
      } catch (FileCannotBeAdded $e) {
        return back()->withErrors(['avatar' => 'Could not upload avatar']);
      }
    }

    return redirect(route('profile.index'));
  }

  public function deleteAvatar()
  {
    $user = auth()->user();

    // Clear avatar media collection
    $user->clearMediaCollection('avatar');

    return redirect(route('profile.index'));
  }

  /**
   * Delete the user's account.
   */
  public function destroy(Request $request): RedirectResponse
  {
    $request->validate([
      'password' => ['required', 'current_password'],
    ]);

    $user = $request->user();

    Auth::logout();

    $user->delete();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return Redirect::to('/');
  }
}
