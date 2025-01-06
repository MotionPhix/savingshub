<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
  public function viewAny(User $user)
  {
    return true; // Allow all users to view loans
  }

  public function view(User $user, Loan $loan)
  {
    return $user->groupMember->id === $loan->group_member_id;
  }

  public function create(User $user)
  {
    return true; // Allow all users to create loans
  }

  public function update(User $user, Loan $loan)
  {
    return $user->groupMember->id === $loan->group_member_id;
  }

  public function delete(User $user, Loan $loan)
  {
    return $user->groupMember->id === $loan->group_member_id;
  }
}
