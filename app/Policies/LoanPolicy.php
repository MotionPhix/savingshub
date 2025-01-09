<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
  public function viewAny(User $user, Group $group)
  {
    // Check if user is a member of the group
    return $group->members()
      ->where('user_id', $user->id)
      ->exists();
  }

  public function view(User $user, Loan $loan)
  {
    // User can view if they are the loan owner or a group admin
    return $loan->user_id === $user->id ||
      $loan->group->members()
        ->where('user_id', $user->id)
        ->where('role', 'admin')
        ->exists();
  }

  public function create(User $user, Group $group)
  {
    // Check if user is an active member of the group
    $membership = $group->members()
      ->where('user_id', $user->id)
      ->where('status', 'active')
      ->first();

    return $membership && !$membership->hasActiveLoan();
  }

  public function update(User $user, Loan $loan)
  {
    // Only group admins can update loan details
    return $loan->group->members()
      ->where('user_id', $user->id)
      ->where('role', 'admin')
      ->exists();
  }

  public function approve(User $user, Loan $loan)
  {
    // Only group admins can approve loans
    return $loan->group->members()
      ->where('user_id', $user->id)
      ->where('role', 'admin')
      ->exists();
  }

  public function makePayment(User $user, Loan $loan)
  {
    // User can make payment if they are the loan owner
    return $loan->user_id === $user->id &&
      $loan->status === 'active';
  }

  public function restructure(User $user, Loan $loan)
  {
    // Only group admins can restructure loans
    return $loan->group->members()
      ->where('user_id', $user->id)
      ->where('role', 'admin')
      ->exists();
  }
}
