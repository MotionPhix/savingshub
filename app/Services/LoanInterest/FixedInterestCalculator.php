<?php

namespace App\Services\LoanInterest;

use App\Contracts\LoanInterestCalculatorInterface;
use App\Models\Group;
use App\Models\User;
use App\Models\Contribution;
use App\Models\Loan;
use Carbon\Carbon;

class FixedInterestCalculator implements LoanInterestCalculatorInterface
{
  public function calculateInterest(Group $group, float $loanAmount, User $user): float
  {
    // Implement more precise fixed interest calculation
    $interestRate = $group->base_interest_rate;

    // Optional: Apply additional risk assessment
    $riskFactor = $this->calculateRiskFactor($user, $group);
    $adjustedRate = $interestRate * (1 + $riskFactor);

    // Use precise decimal calculation
    return round($loanAmount * ($adjustedRate / 100), 2);
  }

  private function calculateRiskFactor(User $user, Group $group): float
  {
    // Basic risk assessment based on user's financial history
    $defaultRisk = 0;

    // Check past loan repayment history
    $pastLoans = Loan::whereHas('groupMember', function ($query) use ($user, $group) {
      $query->where('user_id', $user->id)
        ->where('group_id', $group->id);
    })->get();

    $lateLoans = $pastLoans->filter(function ($loan) {
      return $loan->status === 'overdue';
    });

    // Adjust risk based on late loans
    $riskModifier = $lateLoans->count() * 0.1;

    return min($defaultRisk + $riskModifier, 0.5); // Cap risk at 50%
  }
}

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

class TieredInterestCalculator implements LoanInterestCalculatorInterface
{
  public function calculateInterest(Group $group, float $loanAmount, User $user): float
  {
    $tiers = json_decode($group->interest_tiers, true) ?? [];

    // Enhanced tier sorting and matching
    $sortedTiers = $this->sortAndValidateTiers($tiers);

    // Find most appropriate tier with additional logic
    $selectedTier = $this->findAppropriateInterestTier(
      $sortedTiers,
      $loanAmount,
      $this->calculateUserRiskProfile($user, $group)
    );

    // Fallback to base rate if no tier matches
    $interestRate = $selectedTier['interest_rate'] ?? $group->base_interest_rate;

    return round($loanAmount * ($interestRate / 100), 2);
  }

  private function sortAndValidateTiers(array $tiers): array
  {
    // Validate and sort tiers
    $validTiers = array_filter($tiers, function ($tier) {
      return isset($tier['min_amount']) &&
        isset($tier['interest_rate']) &&
        $tier['min_amount'] >= 0 &&
        $tier['interest_rate'] >= 0;
    });

    usort($validTiers, fn($a, $b) => $b['min_amount'] <=> $a['min_amount']);

    return $validTiers;
  }

  private function calculateUserRiskProfile(User $user, Group $group): float
  {
    // Similar to variable interest calculator's risk assessment
    // Return a risk score between 0 and 1
    return 0.5; // Placeholder
  }

  private function findAppropriateInterestTier(
    array $tiers,
    float $loanAmount,
    float $userRiskProfile
  ): ?array {
    foreach ($tiers as $tier) {
      if ($loanAmount >= $tier['min_amount']) {
        // Adjust interest rate based on user risk
        $adjustedInterestRate = $tier['interest_rate'] * (1 + $userRiskProfile);

        return array_merge($tier, [
          'interest_rate' => min($adjustedInterestRate, 15) // Cap at 15%
        ]);
      }
    }

    return null;
  }
}
