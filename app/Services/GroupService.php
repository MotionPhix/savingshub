<?php


namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GroupService
{
  public function createGroup(User $user, array $data): Group
  {
    return DB::transaction(function () use ($user, $data) {
      $group = Group::create([
        'name' => $data['name'],
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

  /*public function inviteMembers(Group $group, array $emails): array
  {
    $invitations = [];
    foreach ($emails as $email) {
      $user = User::where('email', $email)->first();
      if ($user) {
        $invitations[] = $group->members()->create([
          'user_id' => $user->id,
          'role' => 'member'
        ]);
      }
    }
    return $invitations;
  }*/

  public function inviteMembers(Group $group, array $emails)
  {
    return DB::transaction(function () use ($group, $emails) {
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

      return $invitations;
    });
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
}
