<?php

namespace App\Contracts;

use App\Models\Group;
use App\Models\User;

interface LoanInterestCalculatorInterface
{
  public function calculateInterest(Group $group, float $loanAmount, User $user): float;
}
