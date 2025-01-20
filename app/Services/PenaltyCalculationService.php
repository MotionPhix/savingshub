<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\Group;
use Carbon\Carbon;

class PenaltyCalculationService
{
  /**
   * Calculate penalty for overdue contributions
   */
  public function calculatePenalty(
    Group $group,
    Contribution $contribution = null,
    float $contributionAmount
  ): array {
    // Retrieve penalty configuration
    $penaltyConfig = $this->getPenaltyConfiguration($group);

    // Determine overdue status and duration
    $overdueDetails = $this->calculateOverdueDuration($group, $contribution);

    // Calculate base penalty
    $basePenalty = $this->calculateBasePenalty(
      $contributionAmount,
      $penaltyConfig,
      $overdueDetails
    );

    // Apply progressive penalty if configured
    $progressivePenalty = $this->applyProgressivePenalty(
      $basePenalty,
      $overdueDetails['overdue_months']
    );

    // Calculate total penalty
    $totalPenalty = $this->normalizePenalty(
      $progressivePenalty,
      $contributionAmount,
      $penaltyConfig
    );

    return [
      'is_overdue' => $overdueDetails['is_overdue'],
      'overdue_duration' => $overdueDetails['overdue_months'],
      'base_penalty' => $basePenalty,
      'progressive_penalty' => $progressivePenalty,
      'total_penalty' => $totalPenalty,
      'penalty_percentage' => $this->calculatePenaltyPercentage($totalPenalty, $contributionAmount)
    ];
  }

  /**
   * Retrieve penalty configuration from group settings
   */
  private function getPenaltyConfiguration(Group $group): array
  {
    return [
      'base_penalty_rate' => $group->penalty_fee_percentage ?? 0.05, // Default 5%
      'max_penalty_rate' => 0.1, // Default max 25%
      'progressive_penalty_multiplier' => $group->progressive_penalty_multiplier ?? 1.5,
      'grace_period_days' => $group->allow_contributions_until ?? 5,
      'penalty_calculation_method' => 'standard'
    ];
  }

  /**
   * Calculate overdue duration
   */
  private function calculateOverdueDuration(
    Group $group,
    ?Contribution $contribution = null
  ): array {
    // Use the contribution date or current group contribution period
    $referenceDate = $contribution
      ? Carbon::parse($contribution->contribution_date)
      : $this->getCurrentContributionPeriodStart($group);

    // Calculate overdue period
    $currentDate = now();
    $toleranceEnd = $this->getToleranceEndDate($group, $referenceDate);

    // Check if overdue
    $isOverdue = $currentDate->isAfter($toleranceEnd);

    // Calculate overdue months
    $overdueMonths = $isOverdue
      ? $referenceDate->diffInMonths($currentDate)
      : 0;

    return [
      'is_overdue' => $isOverdue,
      'overdue_months' => $overdueMonths,
      'reference_date' => $referenceDate,
      'tolerance_end_date' => $toleranceEnd
    ];
  }

  /**
   * Get current contribution period start date
   */
  private function getCurrentContributionPeriodStart(Group $group): Carbon
  {
    $currentDate = now();

    // Assuming contributions are due after the 20th of each month
    $contributionPeriodStart = Carbon::create(
      $currentDate->year,
      $currentDate->month,
      20
    );

    // If current date is before the 20th, use previous month
    if ($currentDate->day < 20) {
      $contributionPeriodStart->subMonth();
    }

    return $contributionPeriodStart;
  }

  /**
   * Get tolerance end date for contributions
   */
  private function getToleranceEndDate(Group $group, Carbon $referenceDate): Carbon
  {
    return $referenceDate
      ->clone()
      ->addMonth()
      ->day($group->allow_contributions_until)
      ->endOfDay();
  }

  /**
   * Calculate base penalty
   */
  private function calculateBasePenalty(
    float $contributionAmount,
    array $penaltyConfig,
    array $overdueDetails
  ): float {
    // No penalty if not overdue
    if (!$overdueDetails['is_overdue']) {
      return 0;
    }

    // Calculate base penalty
    return bcmul(
      $contributionAmount,
      $penaltyConfig['base_penalty_rate'],
      2
    );
  }

  /**
   * Apply progressive penalty based on overdue duration
   */
  private function applyProgressivePenalty(
    float $basePenalty,
    int $overdueMonths
  ): float {
    // Increase penalty for each additional month overdue
    return bcmul(
      $basePenalty,
      pow(1.5, $overdueMonths),
      2
    );
  }

  /**
   * Normalize penalty to ensure it doesn't exceed maximum
   */
  private function normalizePenalty(
    float $progressivePenalty,
    float $contributionAmount,
    array $penaltyConfig
  ): float {
    $maxPenalty = bcmul(
      $contributionAmount,
      $penaltyConfig['max_penalty_rate'],
      2
    );

    return min($progressivePenalty, $maxPenalty);
  }

  /**
   * Calculate penalty percentage
   */
  private function calculatePenaltyPercentage(
    float $penaltyAmount,
    float $contributionAmount
  ): float {
    return $contributionAmount > 0
      ? round(($penaltyAmount / $contributionAmount) * 100, 2)
      : 0;
  }

  /**
   * Determine if a contribution requires a penalty
   */
  public function requiresPenalty(Group $group, ?Contribution $contribution = null): bool
  {
    $overdueDetails = $this->calculateOverdueDuration($group, $contribution);
    return $overdueDetails['is_overdue'];
  }
}
