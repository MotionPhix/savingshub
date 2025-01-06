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
        'description' => $data['description'],
        'contribution_frequency' => $data['contribution_frequency'],
        'contribution_amount' => $data['contribution_amount'],
        'duration_months' => $data['duration_months'],
        'loan_interest_type' => $data['loan_interest_type'],
        'base_interest_rate' => $data['base_interest_rate'],
        'max_loan_amount' => $data['max_loan_amount'],
        'require_group_approval' => $data['require_group_approval'],
      ]);

      // Add the creator as a member
      $group->members()->create([
        'user_id' => $user->id,
        'role' => 'admin'
      ]);

      return $group;
    });
  }

  public function updateGroup(Group $group, array $data): Group
  {
    $group->update($data);
    return $group;
  }

  public function inviteMembers(Group $group, array $emails): array
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
  }

  public function acceptGroupInvitation(User $user, Group $group): GroupMember
  {
    return $group->members()->create([
      'user_id' => $user->id,
      'role' => 'member'
    ]);
  }

  public function removeMember(Group $group, User $user): void
  {
    $group->members()->where('user_id', $user->id)->delete();
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
