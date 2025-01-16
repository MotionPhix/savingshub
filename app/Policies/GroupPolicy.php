<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Models\GroupMember;

class GroupPolicy
{
  public function viewAny(User $user)
  {
    // Users can view groups they are part of or public groups
    return $user->isParticipant();
  }

  public function view(User $user, Group $group)
  {
    // User can view group if:
    // 1. They are the group creator
    // 2. They are a group member
    // 3. The group is public
    return $user->id === $group->created_by ||
      $group->members()->where('user_id', $user->id)->exists() ||
      $group->is_public;
  }

  public function create(User $user)
  {
    // Comprehensive group creation checks
    return $user->canCreateGroup();
      // && $user->groups()->count() < config('groups.max_groups_per_user', 5);
  }

  public function update(User $user, Group $group): bool
  {
    // Only group creator or group admins can update group details
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $user->id === $group->created_by ||
      ($membership && in_array($membership->role, ['admin', 'secretary']));
  }

  public function delete(User $user, Group $group): bool
  {
    // Only group creator or primary admin can delete the group
    return $user->id === $group->created_by;
  }

  public function inviteMembers(User $user, Group $group): bool
  {
    // Check if user can add members based on group settings and user role
    $membership = $group->members()->where('user_id', $user->id)->first();

    // If member invites are disabled, only admins can invite
    if (!$group->allow_member_invites) {
      return $user->id === $group->created_by ||
        ($membership && $membership->role === 'admin');
    }

    // If member invites are allowed, check user's role
    return $membership && in_array($membership->role, ['admin', 'secretary']);
  }

  public function assign(User $user, Group $group, string $role = 'member'): bool
  {
    // Check if user can assign member roles
    $membership = $group->members()->where('user_id', $user->id)->first();

    if ($role !== 'member') {
      // If member invites are allowed, check user's role
      return $membership && in_array($membership->role, ['admin', 'secretary']);
    }

    return (bool)$membership;
  }

  public function removeMember(User $user, Group $group, User $targetUser): bool
  {
    $userMembership = $group->members()->where('user_id', $user->id)->first();
    $targetMembership = $group->members()->where('user_id', $targetUser->id)->first();

    // Creator can always remove members
    if ($user->id === $group->created_by) {
      return true;
    }

    // Admins can remove members except the creator
    return $userMembership &&
      $userMembership->role === 'admin' &&
      $targetUser->id !== $group->created_by;
  }

  public function manageLoanSettings(User $user, Group $group)
  {
    // Only group creator or admins can manage loan settings
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $user->id === $group->created_by ||
      ($membership && $membership->role === 'admin');
  }

  public function manageContributionSettings(User $user, Group $group)
  {
    // Similar to loan settings management
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $user->id === $group->created_by ||
      ($membership && $membership->role === 'admin');
  }

  public function requestLoan(User $user, Group $group)
  {
    // Check if user can request a loan in the group
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $membership &&
      $membership->status === 'active' &&
      $group->canAcceptNewMembers() &&
      $this->hasMetLoanRequirements($membership);
  }

  public function approveLoan(User $user, Group $group)
  {
    // Only admins or specific roles can approve loans
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $membership &&
      in_array($membership->role, ['admin', 'treasurer']) &&
      $group->require_group_approval;
  }

  public function makeContribution(User $user, Group $group)
  {
    // Check if user can make a contribution
    $membership = $group->members()->where('user_id', $user->id)->first();

    return $membership &&
      $membership->status === 'active' &&
      $group->status === 'active';
  }

  // Helper method to check loan requirements
  protected function hasMetLoanRequirements(GroupMember $membership)
  {
    // Implement specific loan requirement checks
    // E.g., minimum contribution amount, minimum membership duration
    return true; // Placeholder
  }
}
