<?php

namespace App\Http\Controllers;

use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\NewGroupMemberNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class GroupInvitationController extends Controller
{
  use AuthorizesRequests;

  public function accept($token)
  {
    $invitation = GroupInvitation::where('token', $token)
      ->where('expires_at', '>', now())
      ->whereNull('accepted_at')
      ->firstOrFail();

    // If user is not logged in, redirect to login with intended URL
    if (!Auth::check()) {
      return redirect()->route('login')->with('invitation_token', $token);
    }

    return $this->processInvitation($invitation);
  }

  protected function processInvitation(GroupInvitation $invitation)
  {
    // Ensure user's email matches invitation
    if (Auth::user()->email !== $invitation->email) {
      return back()->with('error', 'This invitation is not for your email address.');
    }

    try {
      // Create group member
      $invitation->group->members()->create([
        'user_id' => Auth::id(),
        'role' => $invitation->role,
        'status' => 'active',
        'joined_at' => now()
      ]);

      // Mark invitation as accepted
      $invitation->update([
        'accepted_at' => now()
      ]);

      // Log the group join activity
      activity()
        ->performedOn($invitation->group)
        ->causedBy(Auth::user())
        ->withProperties([
          'role' => $invitation->role,
          'invitation_id' => $invitation->id
        ])
        ->log('joined_group');

      // Send welcome notification to group admins
      $invitation->group->members()
        ->where('role', 'admin')
        ->get()
        ->each(function ($admin) use ($invitation) {
          $admin->user->notify(new NewGroupMemberNotification(
            $invitation->group,
            Auth::user(),
            $invitation->role
          ));
        });

      // Redirect to the group page
      return redirect()
        ->route('groups.show', $invitation->group)
        ->with('success', "Welcome to {$invitation->group->name}!");
    } catch (\Exception $e) {
      return back()->with('error', 'Could not accept invitation: ' . $e->getMessage());
    }
  }

  public function decline($token)
  {
    $invitation = GroupInvitation::where('token', $token)
      ->where('expires_at', '>', now())
      ->whereNull('accepted_at')
      ->firstOrFail();

    // Soft delete the invitation
    $invitation->delete();

    return redirect()
      ->route('dashboard')
      ->with('info', "You have declined the invitation to {$invitation->group->name}.");
  }

  public function registerWithInvitation(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:users,email',
      'password' => 'required|min:8|confirmed',
      'invitation_token' => 'required|exists:group_invitations,token'
    ]);

    // Find the invitation
    $invitation = GroupInvitation::where('token', $validated['invitation_token'])
      ->where('email', $validated['email'])
      ->where('accepted_at', null)
      ->where('expires_at', '>', now())
      ->firstOrFail();

    // Create user
    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'email_verified_at' => now()
    ]);

    // Log in the user
    Auth::login($user);

    // Accept the invitation
    $group = $invitation->group;

    $membership = $group->members()->create([
      'user_id' => $user->id,
      'role' => $invitation->role,
      'status' => 'active',
      'joined_at' => now()
    ]);

    // Mark invitation as accepted
    $invitation->update(['accepted_at' => now()]);

    // Log the activity
    activity()
      ->performedOn($group)
      ->causedBy($user)
      ->withProperties([
        'invitation_id' => $invitation->id,
        'role' => $invitation->role
      ])
      ->log('group_invitation_accepted_with_registration');

    return redirect()->route('groups.show', $group->uuid)
      ->with('success', "Welcome to {$group->name}!");
  }

  public function resendInvitation(Request $request, GroupInvitation $invitation)
  {
    // Authorize the resend action
    $this->authorize('resend', $invitation);

    // Check if invitation is still valid
    if (!$invitation->isValid()) {
      return back()->with('error', 'This invitation is no longer valid.');
    }

    // Regenerate token
    $invitation->update([
      'token' => Str::random(60),
      'expires_at' => now()->addDays(7)
    ]);

    // Resend notification
    Notification::route('mail', $invitation->email)
      ->notify(new GroupInvitationNotification(
        $invitation->group,
        $invitation
      ));

    return back()->with('success', 'Invitation resent successfully.');
  }
}
