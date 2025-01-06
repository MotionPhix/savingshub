<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
  public function viewAny(User $user)
  {
    // Only participants can view groups
    return $user->isParticipant();
  }

  public function view(User $user, Group $group)
  {
    // User can view group if they are a member
    return $user->id === $group->created_by || $group->members()->where('user_id', $user->id)->exists();
  }

  public function create(User $user)
  {
    // Check if user can create a group based on their user type
    if (!$user->canCreateGroup()) {
      return false;
    }

    return true;
  }

  public function update(User $user, Group $group)
  {
    $membership = $group->members()->where('user_id', $user->id)->first();

    // Only group creator or group admins can update
    return $user->id === $group->created_by || $membership && $membership->isGroupAdmin();
  }

  public function delete(User $user, Group $group)
  {
    // Only group admin can delete
    $membership = $group->members()->where('user_id', $user->id)->first();
    return $membership && $membership->isGroupAdmin();
  }
}
