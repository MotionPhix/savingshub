<?php

namespace App\Services\LoanInterest;

use App\Contracts\LoanInterestCalculatorInterface;
use App\Models\Group;
use App\Models\User;
use App\Models\Loan;

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

/*class TieredInterestCalculator implements LoanInterestCalculatorInterface
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
}*/
