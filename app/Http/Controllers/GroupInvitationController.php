<?php

namespace App\Http\Controllers;

use App\Models\GroupInvitation;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\NewGroupMemberNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    // Create group member
    $invitation->group->members()->create([
      'user_id' => Auth::id(),
      'role' => $invitation->role,
      'status' => 'active'
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
