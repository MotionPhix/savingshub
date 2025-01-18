<?php

namespace App\Services\LoanInterest;

use App\Contracts\LoanInterestCalculatorInterface;
use App\Models\Contribution;
use App\Models\Group;
use App\Models\Loan;
use App\Models\User;

class VariableInterestCalculator implements LoanInterestCalculatorInterface
{
  public function calculateInterest(Group $group, float $loanAmount, User $user): float
  {
    // More comprehensive variable rate calculation
    $baseRate = $group->base_interest_rate;

    // Multifaceted contribution and financial health assessment
    $contributionScore = $this->calculateContributionScore($user, $group);
    $financialHealthScore = $this->calculateFinancialHealthScore($user, $group);

    // Dynamic rate modifier based on multiple factors
    $rateModifier = $this->calculateRateModifier(
      $contributionScore,
      $financialHealthScore
    );

    // Precise interest calculation with bounds
    $finalRate = max(
      min($baseRate + $rateModifier, 15), // Upper limit
      2 // Lower limit
    );

    return round($loanAmount * ($finalRate / 100), 2);
  }

  private function calculateContributionScore(User $user, Group $group): float
  {
    // Analyze contribution consistency and volume
    $contributions = Contribution::whereHas('groupMember', function ($query) use ($user, $group) {
      $query->where('user_id', $user->id)
        ->where('group_id', $group->id);
    })->get();

    // Calculate contribution metrics
    $totalContributions = $contributions->sum('amount');
    $contributionFrequency = $contributions->count();
    $consistencyPeriod = $contributions->max('created_at')->diffInMonths(
      $contributions->min('created_at')
    );

    // Normalized scoring
    $volumeScore = min($totalContributions / 10000, 1);
    $frequencyScore = min($contributionFrequency / 12, 1);
    $consistencyScore = min($consistencyPeriod / 24, 1);

    return ($volumeScore * 0.5) + ($frequencyScore * 0.3) + ($consistencyScore * 0.2);
  }

  private function calculateFinancialHealthScore(User $user, Group $group): float
  {
    // Assess overall financial health
    $pastLoans = Loan::whereHas('groupMember', function ($query) use ($user, $group) {
      $query->where('user_id', $user->id)
        ->where('group_id', $group->id);
    })->get();

    $completedLoans = $pastLoans->filter(function ($loan) {
      return $loan->status === 'completed';
    });

    $lateLoans = $pastLoans->filter(function ($loan) {
      return $loan->status === 'overdue';
    });

    $loanRepaymentScore = $completedLoans->count() > 0
      ? 1 - ($lateLoans->count() / $pastLoans->count())
      : 0.5;

    return $loanRepaymentScore;
  }

  private function calculateRateModifier(float $contributionScore, float $financialHealthScore): float
  {
    // Sophisticated rate modification
    return round(
      (1 - $contributionScore) * 3 -
      ($financialHealthScore * 2),
      2
    );
  }
}
