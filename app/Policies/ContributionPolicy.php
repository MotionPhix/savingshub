<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
  public function viewAny(User $user)
  {
    return true; // Allow all users to view contributions
  }

  public function view(User $user, Contribution $contribution)
  {
    return $user->groupMember->id === $contribution->group_member_id;
  }

  public function create(User $user)
  {
    return true; // Allow all users to create contributions
  }

  public function update(User $user, Contribution $contribution)
  {
    return $user->groupMember->id === $contribution->group_member_id;
  }

  public function delete(User $user, Contribution $contribution)
  {
    return $user->groupMember->id === $contribution->group_member_id;
  }
}
