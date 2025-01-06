<?php

namespace App\Services\LoanInterest;

use App\Contracts\LoanInterestCalculatorInterface;
use App\Models\Group;
use InvalidArgumentException;

class LoanInterestCalculatorFactory
{
  public static function create(Group $group): LoanInterestCalculatorInterface
  {
    return match($group->loan_interest_type) {
      'fixed' => new FixedInterestCalculator(),
      'variable' => new VariableInterestCalculator(),
      'tiered' => new TieredInterestCalculator(),
      default => throw new InvalidArgumentException("Invalid interest calculation type")
    };
  }
}
