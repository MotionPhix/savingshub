<?php


namespace App\Services;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class GroupService
{
  // This allows a user to deal with one group at a time. all actions taken will be
  // on the activated group until a user explicitly switches to another group
  public function activateGroup(Request $request, Group $group): void
  {
    DB::transaction(function () use ($request, $group) {
      // Verify user is a member of the group
      $groupMember = GroupMember::where('group_id', $group->id)
        ->where('user_id', Auth::id())
        ->where('status', 'active')
        ->first();

      // If not a member, throw authorization exception
      if (!$groupMember) {
        abort(403, 'You are not a member of this group');
      }

      // Store the active group in the session
      $request->session()->put('active_group_id', $group->id);

      // Optional: You can also store the user's role in the group
      $request->session()->put('active_group_role', $groupMember->role);
    });
  }

  public function createGroup(User $user, array $data): Group
  {
    return DB::transaction(function () use ($user, $data) {
      $group = Group::create([
        'name' => $data['name'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'description' => $data['description'] ?? null,
        'contribution_frequency' => $data['contribution_frequency'],
        'contribution_amount' => $data['contribution_amount'],
        'duration_months' => $data['duration_months'],
        'loan_interest_type' => $data['loan_interest_type'],
        'base_interest_rate' => $data['base_interest_rate'],
        'max_loan_amount' => $data['max_loan_amount'] ?? null,
        'require_group_approval' => $data['require_group_approval'] ?? true,
        'min_members' => $data['min_members'] ?? 2,
        'max_members' => $data['max_members'] ?? 10,
        'is_public' => $data['is_public'] ?? true,
        'created_by' => $user->id,
        'status' => 'active',
      ]);

      // Add the creator as a member
      $group->members()->create([
        'user_id' => $user->id,
        'role' => 'admin',
        'status' => 'active',
        'joined_at' => now()
      ]);

      return $group;
    });
  }

  public function updateGroup(Group $group, array $data): Group
  {
    $group->update($data);
    return $group;
  }

  public function inviteMembers(array $emails, string $message = null)
  {
    $invitations = [];
    $existingEmails = [];
    $failedEmails = [];

    $group = Group::where('id',  session('active_group_id'))->first();

    foreach ($emails as $email) {
      try {
        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
          // If user exists, check if already a member
          $isMember = $group->members()->where('user_id', $existingUser->id)->exists();

          if ($isMember) {
            $existingEmails[] = $email;
            continue;
          }
        }

        // Create invitation
        $invitation = GroupInvitation::create([
          'group_id' => session('active_group_id'),
          'email' => $email,
          'role' => $validated['role'] ?? 'member',
          'token' => Str::random(60),
          'expires_at' => now()->addDays(7),
          'invited_by' => auth()->id()
        ]);

        $invitations[] = $invitation;

        // Send invitation notification
        Notification::route('mail', $email)
          ->notify(new GroupInvitationNotification(
            $group,
            $invitation,
            $message
          ));

      } catch (\Exception $e) {
        // Log the error and track failed emails
        Log::error('Group invitation failed', [
          'email' => $email,
          'group_id' => $group->id,
          'error' => $e->getMessage()
        ]);
        $failedEmails[] = $email;
      }
    }

    // Prepare response
    return [
      'success' => count($invitations),
      'existing' => $existingEmails,
      'failed' => $failedEmails
    ];

    /*return DB::transaction(function () use ($group, $emails) {
      $invitations = [];

      foreach ($emails as $email) {
        // Find or create user
        $user = User::firstOrCreate(
          ['email' => $email],
          [
            'name' => explode('@', $email)[0],
            'password' => bcrypt('password'), // Temporary password
            'email_verified_at' => null
          ]
        );

        // Create group invitation
        $invitation = $group->members()->create([
          'user_id' => $user->id,
          'status' => 'invited',
          'invited_by' => auth()->id(),
          'invited_at' => now()
        ]);

        $invitations[] = $invitation;

        // Send invitation notification
        Notification::send($user, new GroupInvitationNotification($group, $invitation));
      }

      return $response;
    });*/
  }

  public function acceptGroupInvitation(User $user, Group $group)
  {
    return DB::transaction(function () use ($user, $group) {
      // Find the existing invitation
      $membership = $group->members()
        ->where('user_id', $user->id)
        ->where('status', 'invited')
        ->firstOrFail();

      // Update membership status
      $membership->update([
        'status' => 'active',
        'joined_at' => now(),
        'role' => 'member' // Default role
      ]);

      return $membership;
    });
  }

  /*public function acceptGroupInvitation(User $user, Group $group): GroupMember
  {
    return $group->members()->create([
      'user_id' => $user->id,
      'role' => 'member'
    ]);
  }*/

  public function removeMember(Group $group, User $user): bool
  {
    // $group->members()->where('user_id', $user->id)->delete();
    return DB::transaction(function () use ($group, $user) {
      // Check if user is a member of the group
      $membership = $group->members()
        ->where('user_id', $user->id)
        ->firstOrFail();

      // Prevent removing the last admin
      if ($membership->role === 'admin' &&
        $group->members()->where('role', 'admin')->count() <= 1) {
        throw new \Exception('Cannot remove the last group admin');
      }

      // Check for active loans or contributions
      $hasActiveLoans = $user->loans()
        ->where('group_id', $group->id)
        ->where('status', 'active')
        ->exists();

      if ($hasActiveLoans) {
        throw new \Exception('Cannot remove member with active loans');
      }

      // Remove membership
      $membership->delete();

      return true;
    });
  }

  public function changeMemberRole(Group $group, User $user, string $role): void
  {
    $group->members()->where('user_id', $user->id)->update(['role' => $role]);
  }

  public function leaveGroup(User $user, Group $group): void
  {
    $group->members()->where('user_id', $user->id)->delete();
  }

  public function dissolveGroup(Group $group): void
  {
    $group->members()->delete(); // Remove all members
    $group->delete(); // Delete the group
  }

  public function updateSettings(Group $group, array $data, User $user): Group
  {
    // Merge existing settings with new settings
    $currentSettings = $group->settings ?? [];
    $currentNotificationPreferences = $group->notification_preferences ?? [];

    // Update settings
    if (isset($data['settings'])) {
      $mergedSettings = array_merge($currentSettings, $data['settings']);
      $group->setAttribute('settings', $mergedSettings);
    }

    // Update notification preferences
    if (isset($data['notification_preferences'])) {
      $mergedNotificationPreferences = array_merge(
        $currentNotificationPreferences,
        $data['notification_preferences']
      );

      $group->setAttribute(
        'notification_preferences',
        $mergedNotificationPreferences
      );
    }

    // Update other group attributes
    $updateFields = [
      'is_public',
      'allow_member_invites',
      'contribution_frequency',
      'contribution_amount'
    ];

    foreach ($updateFields as $field) {
      if (isset($data[$field])) {
        $group->setAttribute($field, $data[$field]);
      }
    }

    // Save the changes
    $group->save();

    // Log the settings update
    /*activity()
      ->performedOn($group)
      ->causedBy($user)
      ->log('Group settings updated');*/

    return $group;
  }

  public function validateGroupSettingsUpdate(Group $group, User $user): bool
  {
    // Additional custom validation logic
    return $group->creator_id === $user->id ||
      $user->hasRole('admin') ||
      $group->members()
        ->where('user_id', $user->id)
        ->where('role', 'admin')
        ->exists();
  }
}
