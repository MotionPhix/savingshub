<?php

namespace App\Services\LoanInterest;

use App\Contracts\LoanInterestCalculatorInterface;
use App\Exceptions\LoanException;
use App\Models\Group;
use App\Models\User;

class TieredInterestCalculator implements LoanInterestCalculatorInterface
{
  public function calculateInterest(Group $group, float $loanAmount, User $user): float
  {
    // Validate input
    $this->validateInputs($group, $loanAmount, $user);

    // Decode and validate tiers
    $tiers = $this->prepareTiers($group);

    // Calculate user risk profile
    $userRiskProfile = $this->calculateUserRiskProfile($user, $group);

    // Find appropriate tier
    $selectedTier = $this->findAppropriateInterestTier(
      $tiers,
      $loanAmount,
      $userRiskProfile
    );

    // Calculate interest
    return $this->computeInterest(
      $loanAmount,
      $selectedTier,
      $group->base_interest_rate
    );
  }

  private function validateInputs(Group $group, float $loanAmount, User $user): void
  {
    // Validate group
    if ($group->loan_interest_type !== 'tiered') {
      throw LoanException::invalidLoanStatus("Non-tiered interest type: {$group->loan_interest_type}");
    }

    // Validate loan amount
    if ($loanAmount <= 0) {
      throw LoanException::invalidLoanAmount("Loan amount must be positive");
    }

    // Optional: Check maximum loan amount
    if ($group->max_loan_amount && $loanAmount > $group->max_loan_amount) {
      throw LoanException::exceedsMaxLoanAmount();
    }
  }

  private function prepareTiers(Group $group): array
  {
    // Decode JSON if it's a string, otherwise use as-is
    $rawTiers = is_string($group->interest_tiers)
      ? json_decode($group->interest_tiers, true)
      : $group->interest_tiers;

    // Handle null or empty tiers
    if (empty($rawTiers)) {
      throw LoanException::invalidInterestTiers(
        "No interest tiers defined"
      );
    }

    // Validate and prepare tiers
    $validTiers = array_filter($rawTiers, function ($tier) {
      return isset($tier['min_amount'], $tier['max_amount'], $tier['rate']) &&
        is_numeric($tier['min_amount']) &&
        is_numeric($tier['max_amount']) &&
        is_numeric($tier['rate']) &&
        $tier['min_amount'] >= 0 &&
        $tier['max_amount'] > $tier['min_amount'] &&
        $tier['rate'] >= 0;
    });

    // Check if any valid tiers remain after filtering
    if (empty($validTiers)) {
      throw LoanException::invalidInterestTiers("No valid interest tiers found");
    }

    // Ensure tiers are sorted
    usort($validTiers, fn($a, $b) => $a['min_amount'] <=> $b['min_amount']);

    // Validate tier sequence
    $this->validateTierSequence($validTiers);

    return $validTiers;
  }

  private function validateTierSequence(array $tiers): void
  {
    // Check first tier starts at 0
    if ($tiers[0]['min_amount'] !== 0) {
      throw LoanException::invalidInterestTiers("First tier must start at 0");
    }

    // Check sequential and non-overlapping tiers
    for ($i = 1; $i < count($tiers); $i++) {
      $prevTier = $tiers[$i - 1];
      $currentTier = $tiers[$i];

      if ($currentTier['min_amount'] !== $prevTier['max_amount'] + 1) {
        throw LoanException::invalidInterestTiers("Tiers must be sequential without gaps");
      }
    }
  }

  private function calculateUserRiskProfile(User $user, Group $group): float
  {
    // Comprehensive risk assessment
    $riskFactors = [
      'loan_history' => $this->calculateLoanHistory($user, $group),
      'contribution_consistency' => $this->calculateContributionConsistency($user, $group),
      'group_participation' => $this->calculateGroupParticipation($user, $group)
    ];

    // Compute weighted risk score
    $riskScore = array_reduce($riskFactors, fn($carry, $factor) => $carry + $factor, 0)
      / count($riskFactors);

    // Normalize and cap risk score
    return max(0, min(1, $riskScore));
  }

  private function findAppropriateInterestTier(
    array $tiers,
    float $loanAmount,
    float $userRiskProfile
  ): array {
    foreach ($tiers as $tier) {
      if ($loanAmount >= $tier['min_amount'] && $loanAmount <= $tier['max_amount']) {
        // Dynamically adjust interest rate based on risk
        $adjustedRate = $this->adjustInterestRateByRisk(
          $tier['rate'],
          $userRiskProfile
        );

        return array_merge($tier, ['adjusted_rate' => $adjustedRate]);
      }
    }

    // If no tier matches, use the last (highest) tier
    $lastTier = end($tiers);
    return array_merge($lastTier, [
      'adjusted_rate' => $this->adjustInterestRateByRisk(
        $lastTier['rate'],
        $userRiskProfile
      )
    ]);
  }

  private function adjustInterestRateByRisk(float $baseRate, float $riskProfile): float
  {
    // Adjust rate based on risk profile
    // Higher risk increases interest rate
    $riskAdjustment = $baseRate * $riskProfile * 0.5; // Max 50% increase
    $adjustedRate = $baseRate + $riskAdjustment;

    // Ensure rate doesn't exceed maximum
    return min($adjustedRate, 25); // Cap at 25%
  }

  private function computeInterest(
    float $loanAmount,
    array $tier,
    float $baseRate
  ): float {
    // Use adjusted rate or fallback to base rate
    $interestRate = $tier['adjusted_rate'] ?? $baseRate;

    // Calculate and round interest
    return round($loanAmount * ($interestRate / 100), 2);
  }

  // Helper methods for risk calculation (placeholders)
  private function calculateLoanHistory(User $user, Group $group): float
  {
    // Implement loan repayment history logic
    return 0.5; // Placeholder
  }

  private function calculateContributionConsistency(User $user, Group $group): float
  {
    // Implement contribution consistency logic
    return 0.5; // Placeholder
  }

  private function calculateGroupParticipation(User $user, Group $group): float
  {
    // Implement group participation logic
    return 0.5; // Placeholder
  }
}
