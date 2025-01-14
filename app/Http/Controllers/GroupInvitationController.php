<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\NewGroupMemberNotification;
use App\Services\GroupActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GroupInvitationController extends Controller
{
  use AuthorizesRequests;

  public function __construct(
    protected GroupActivityService $activityService
  ) {}

  public function handleInvitationLink($token)
  {
    try {
      $invitation = GroupInvitation::where('token', $token)
        ->where('accepted_at', null)
        ->firstOrFail();

      // Check expiration
      if (now()->greaterThan($invitation->expires_at)) {
        // Log expired invitation attempt
        $this->logInvitationActivity(
          $invitation,
          'invitation_expired',
          ['reason' => 'Invitation link expired']
        );

        return redirect()
          ->route('login')
          ->with(
            'flush',
            'This invitation has expired. Please contact the group admin for a new invitation.'
          );
      }

      // If user is not authenticated
      if (!auth()->check()) {
        // Store the token in the session
        Session::put('pending_invitation_token', $token);

        // Redirect to login with invitation context
        return Inertia('Auth/Login', [
          'invitationToken' => $token,
          'invitationEmail' => $invitation->email,
          'invitationGroup' => $invitation->group->name,
          'canResetPassword' => false,
          'flush' => session('flush'),
        ]);
      }

      // User is already logged in, proceed with invitation acceptance
      return $this->accept($token);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      Log::warning('Invalid invitation attempt', [
        'token' => $token,
        'ip' => request()->ip()
      ]);

      return redirect()->route('login')
        ->withErrors([
          'message' => 'Invalid or used invitation link.'
        ]);
    }
  }

  public function accept($token)
  {
    DB::beginTransaction();

    try {
      $invitation = GroupInvitation::where('token', $token)
        ->where('accepted_at', null)
        ->firstOrFail();

      $user = auth()->user();

      // Validate email
      if ($user->email !== $invitation->email) {
        $this->logInvitationActivity(
          $invitation,
          'invitation_email_mismatch',
          ['provided_email' => $user->email]
        );

        return redirect()->route('login')
          ->with(
            'flush',
            'This invitation is for a different email address.'
          );
      }

      // Create group membership
      $membership = $invitation->group->members()->create([
        'user_id' => $user->id,
        'role' => $invitation->role,
        'status' => 'active',
        'joined_at' => now()
      ]);

      // Mark invitation as accepted
      $invitation->update(['accepted_at' => now()]);

      // Log successful invitation acceptance
      $this->logInvitationActivity(
        $invitation,
        'invitation_accepted',
        ['role' => $invitation->role]
      );

      DB::commit();

      return redirect()
        ->route('groups.show', $invitation->group->uuid)
        ->with('flush', "Welcome to {$invitation->group->name}!");

    } catch (\Exception $e) {
      DB::rollBack();

      // Log detailed error
      Log::error('Invitation acceptance failed', [
        'invitation_id' => $invitation->id ?? null,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return back()
        ->withErrors([
            'message',
            'Could not accept invitation. Please try again.'
          ]
        );
    }
  }

  protected function processInvitation(GroupInvitation $invitation)
  {
    // Ensure user's email matches invitation
    if (Auth::user()->email !== $invitation->email) {
      return Inertia('Auth/Login', [
        'email' => 'This invitation is for a different email address.'
      ]);
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

      // Clear any pending invitation token
      Session::forget('pending_invitation_token');

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
        ->with('flush', "Welcome to {$invitation->group->name}!");
    } catch (\Exception $e) {
      return back()->withErrors([
        'message' => 'Could not accept invitation: ' . $e->getMessage()
      ]);
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

    // Clear any pending invitation token
    Session::forget('pending_invitation_token');

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

  public function manageInvitations(Request $request)
  {
    $group = Group::where('id', session('active_group_id'))->firstOrFail();

    $invitations = $group->invitations()
      ->with('invitedBy')
      ->latest()
      ->paginate(10);

    return Inertia('Groups/Invitations', [
      'group' => $group,
      'invitations' => $invitations
    ]);
  }

  public function cancelInvitation(Request $request, GroupInvitation $invitation)
  {
    // Ensure user has permission to cancel
    $this->authorize('cancel', $invitation);

    $invitation->delete();

    return back()->with('success', 'Invitation cancelled successfully');
  }

  protected function logInvitationActivity(
    GroupInvitation $invitation,
    string $type,
    array $metadata = []
  ) {
    $this->activityService->log(
      $invitation->group,
      $type,
      auth()->user(),
      null,
      array_merge([
        'invitation_id' => $invitation->id,
        'email' => $invitation->email
      ], $metadata)
    );
  }
}
