<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContributionService
{
  public function processScheduledContributions(Group $group)
  {
    return DB::transaction(function () use ($group) {
      // Fetch group members
      $groupMembers = $group->members;

      foreach ($groupMembers as $groupMember) {
        // Create scheduled contribution
        $this->createScheduledContribution($groupMember);
      }
    });
  }

  private function calculateOverdueRate(Collection $contributions): float
  {
    $totalContributions = $contributions->count();
    $overdueContributions = $contributions->filter(fn($c) => $c->status === 'overdue')->count();

    return $totalContributions > 0
      ? $overdueContributions / $totalContributions
      : 0;
  }


  /*private function calculateOverdueRate(Group $group)
  {
    $totalContributions = Contribution::where('group_id', $group->id)->count();
    $overdueContributions = Contribution::where('group_id', $group->id)
      ->where('status', 'overdue')
      ->count();

    return $totalContributions > 0
      ? ($overdueContributions / $totalContributions) * 100
      : 0;
  }*/



  private function createScheduledContribution(GroupMember $groupMember)
  {
    // Determine contribution amount based on group settings
    $contributionAmount = $this->calculateContributionAmount($groupMember);

    return Contribution::createContribution(
      $groupMember,
      $contributionAmount,
      now(), // Use current date or specific logic for contribution date
      'regular'
    );
  }

  private function calculateContributionAmount(GroupMember $groupMember): float
  {
    $group = $groupMember->group;

    // Basic contribution amount from group settings
    $baseAmount = $group->contribution_amount;

    // Optional: Apply dynamic calculation logic
    // Could include factors like:
    // - Member's contribution history
    // - Group's financial goals
    // - Individual member adjustments

    return $baseAmount;
  }

  public function reconcileContributions(Group $group)
  {
    // Find pending contributions
    $pendingContributions = Contribution::where('group_id', $group->id)
      ->where('status', 'pending')
      ->get();

    foreach ($pendingContributions as $contribution) {
      $this->validateAndReconcileContribution($contribution);
    }
  }

  private function validateAndReconcileContribution(Contribution $contribution)
  {
    // Implement validation logic
    // Could involve:
    // - Checking payment confirmations
    // - Verifying transaction references
    // - Matching with external payment systems

    $isValid = $this->validateContributionAgainstPaymentRecords($contribution);

    $contribution->reconcile($isValid);
  }

  private function validateContributionAgainstPaymentRecords(Contribution $contribution): bool
  {
    // Implement sophisticated validation
    try {
      // Example validation strategies
      return $this->validateByPaymentGateway($contribution) &&
        $this->validateByBankStatement($contribution) &&
        $this->checkForDuplicateContributions($contribution);
    } catch (\Exception $e) {
      // Log validation failures
      \Log::warning('Contribution validation failed', [
        'contribution_id' => $contribution->id,
        'error' => $e->getMessage()
      ]);

      return false;
    }
  }

  private function validateByPaymentGateway(Contribution $contribution): bool
  {
    // Integration with payment gateway verification
    // This would be a real implementation with actual payment gateway
    $paymentGateway = app(PaymentGatewayService::class);

    return $paymentGateway->verifyTransaction(
      $contribution->transaction_reference,
      $contribution->amount
    );
  }

  private function validateByBankStatement(Contribution $contribution): bool
  {
    // Bank statement reconciliation logic
    // Could involve matching transactions from bank feeds
    $bankStatementService = app(BankStatementService::class);

    return $bankStatementService->matchTransaction(
      $contribution->user,
      $contribution->amount,
      $contribution->contribution_date
    );
  }

  private function checkForDuplicateContributions(Contribution $contribution): bool
  {
    // Check for potential duplicate contributions
    $duplicates = Contribution::where('user_id', $contribution->user_id)
      ->where('group_id', $contribution->group_id)
      ->where('amount', $contribution->amount)
      ->where('contribution_date', $contribution->contribution_date)
      ->where('id', '!=', $contribution->id)
      ->count();

    return $duplicates === 0;
  }

  // Advanced Contribution Analysis
  public function analyzeGroupContributionHealth(Group $group)
  {
    // Comprehensive group contribution health check
    $contributions = Contribution::where('group_id', $group->id)
      ->selectRaw('
                COUNT(*) as total_contributions,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_contributions,
                SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_contributions,
                AVG(CASE WHEN status = "paid" THEN amount END) as average_contribution,
                SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_collected
            ')
      ->first();

    $memberContributionBreakdown = GroupMember::where('group_id', $group->id)
      ->with(['contributions' => function ($query) {
        $query->selectRaw('
                    group_member_id,
                    COUNT(*) as total_contributions,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_contributions,
                    SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_contributions
                ')
          ->groupBy('group_member_id');
      }])
      ->get()
      ->map(function ($member) {
        return [
          'member_id' => $member->id,
          'user_name' => $member->user->name,
          'contribution_performance' => $this->calculateMemberContributionPerformance(
            $member->contributions->first() ?? null
          )
        ];
      });

    return [
      'group_contribution_summary' => $contributions,
      'member_contribution_breakdown' => $memberContributionBreakdown,
      'health_score' => $this->calculateGroupContributionHealthScore(
        $contributions,
        $memberContributionBreakdown
      )
    ];
  }

  private function calculateMemberContributionPerformance($contributionStats)
  {
    if (!$contributionStats) {
      return [
        'performance_score' => 0,
        'status' => 'no_contributions'
      ];
    }

    $totalContributions = $contributionStats->total_contributions;
    $paidContributions = $contributionStats->paid_contributions;
    $overdueContributions = $contributionStats->overdue_contributions;

    $performanceScore = $totalContributions > 0
      ? ($paidContributions / $totalContributions) * 100
      : 0;

    $status = match (true) {
      $performanceScore === 100 => 'excellent',
      $performanceScore >= 80 => 'good',
      $performanceScore >= 50 => 'needs_improvement',
      default => 'poor'
    };

    return [
      'performance_score' => $performanceScore,
      'total_contributions' => $totalContributions,
      'paid_contributions' => $paidContributions,
      'overdue_contributions' => $overdueContributions,
      'status' => $status
    ];
  }

  private function calculateGroupContributionHealthScore(
    $contributionSummary,
    $memberContributionBreakdown
  ): float
  {
    // Complex health score calculation
    $paidPercentage = $contributionSummary->total_contributions > 0
      ? ($contributionSummary->paid_contributions / $contributionSummary->total_contributions) * 100
      : 0;

    $memberPerformanceScores = collect($memberContributionBreakdown)
      ->pluck('contribution_performance.performance_score');

    $averageMemberPerformance = $memberPerformanceScores->avg();

    // Weighted calculation of health score
    return round(
      ($paidPercentage * 0.6) +
      ($averageMemberPerformance * 0.4),
      2
    );
  }

  // Contribution Projection and Forecasting
  public function forecastGroupContributions(Group $group, int $projectionMonths = 12)
  {
    $historicalData = $this->analyzeGroupContributionHealth($group);
    $averageContribution = $historicalData['group_contribution_summary']->average_contribution;
    $memberCount = $group->members->count();

    return [
      'projected_total_contributions' => $averageContribution * $memberCount * $projectionMonths,
      'best_case_scenario' => $averageContribution * $memberCount * $projectionMonths * 1.2,
      'worst_case_scenario' => $averageContribution * $memberCount * $projectionMonths * 0.8,
      'risk_factors' => $this->identifyContributionRiskFactors($group)
    ];
  }

  private function identifyContributionRiskFactors(Group $group)
  {
    // Analyze potential risks to contribution consistency
    return [
      'historical_overdue_rate' => $this->calculateOverdueRate($group),
      'member_consistency_variance' => $this->calculateMemberConsistencyVariance($group),
      // Add more sophisticated risk analysis
    ];
  }

  private function calculateMemberConsistencyVariance(Group $group)
  {
    // Calculate variance in member contribution consistency
    $memberContributions = DB::table('contributions')
      ->where('group_id', $group->id)
      ->select('user_id',
        DB::raw('COUNT(*) as total_contributions'),
        DB::raw('SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_contributions')
      )
      ->groupBy('user_id')
      ->get();

    $consistencyScores = $memberContributions->map(function ($member) {
      return $member->total_contributions > 0
        ? $member->paid_contributions / $member->total_contributions
        : 0;
    });

    return $this->calculateVariance($consistencyScores->toArray());
  }

  private function calculateVariance(array $numbers): float
  {
    if (empty($numbers)) {
      return 0;
    }

    $mean = array_sum($numbers) / count($numbers);
    $variance = array_reduce($numbers, function ($carry, $item) use ($mean) {
        return $carry + pow($item - $mean, 2);
      }, 0) / count($numbers);

    return sqrt($variance); // Standard deviation
  }

  // Advanced Contribution Recommendation System
  public function generateContributionRecommendations(User $user, Group $group)
  {
    $userContributions = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->get();

    $groupContributionAnalytics = $this->analyzeGroupContributionHealth($group);
    $userContributionPerformance = $this->calculateUserContributionPerformance($user, $group);

    return [
      'personalized_contribution_suggestions' => $this->generatePersonalizedSuggestions(
        $user,
        $group,
        $userContributionPerformance
      ),
      'group_benchmarks' => $this->generateGroupBenchmarks(
        $groupContributionAnalytics
      ),
      'performance_comparison' => $this->compareUserToGroupPerformance(
        $userContributionPerformance,
        $groupContributionAnalytics
      )
    ];
  }

  private function calculateUserContributionPerformance(User $user, Group $group)
  {
    $contributions = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->selectRaw('
                COUNT(*) as total_contributions,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_contributions,
                SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_contributions,
                AVG(CASE WHEN status = "paid" THEN amount END) as average_contribution
            ')
      ->first();

    return [
      'total_contributions' => $contributions->total_contributions,
      'paid_contributions' => $contributions->paid_contributions,
      'overdue_contributions' => $contributions->overdue_contributions,
      'average_contribution' => $contributions->average_contribution,
      'contribution_consistency' => $contributions->total_contributions > 0
        ? $contributions->paid_contributions / $contributions->total_contributions
        : 0
    ];
  }

  private function generatePersonalizedSuggestions(
    User  $user,
    Group $group,
    array $userPerformance
  ): array
  {
    $suggestions = [];

    // Consistency improvement
    if ($userPerformance['contribution_consistency'] < 0.7) {
      $suggestions[] = [
        'type' => 'consistency_improvement',
        'message' => 'Consider setting up automatic contributions to improve consistency.',
        'priority' => 'high'
      ];
    }

    // Amount optimization
    $groupAverageContribution = $group->contribution_amount;
    if ($userPerformance['average_contribution'] < $groupAverageContribution * 0.8) {
      $suggestions[] = [
        'type' => 'contribution_amount',
        'message' => 'Your average contribution is below the group average. Consider increasing your contribution.',
        'current_average' => $userPerformance['average_contribution'],
        'group_average' => $groupAverageContribution,
        'priority' => 'medium'
      ];
    }

    // Overdue contribution management
    if ($userPerformance['overdue_contributions'] > 0) {
      $suggestions[] = [
        'type' => 'overdue_management',
        'message' => 'You have overdue contributions. Create a catch-up plan to get back on track.',
        'overdue_count' => $userPerformance['overdue_contributions'],
        'priority' => 'high'
      ];
    }

    return $suggestions;
  }

  private function generateGroupBenchmarks(array $groupAnalytics): array
  {
    $contributionSummary = $groupAnalytics['group_contribution_summary'];

    return [
      'average_contribution' => $contributionSummary->average_contribution,
      'total_collected' => $contributionSummary->total_collected,
      'contribution_completion_rate' => $contributionSummary->total_contributions > 0
        ? ($contributionSummary->paid_contributions / $contributionSummary->total_contributions) * 100
        : 0
    ];
  }

  private function compareUserToGroupPerformance(
    array $userPerformance,
    array $groupAnalytics
  ): array
  {
    $groupBenchmarks = $this->generateGroupBenchmarks($groupAnalytics);

    return [
      'contribution_consistency' => [
        'user_performance' => $userPerformance['contribution_consistency'] * 100,
        'group_average' => $groupBenchmarks['contribution_completion_rate'],
        'comparison' => $this->calculatePerformanceComparison(
          $userPerformance['contribution_consistency'],
          $groupBenchmarks['contribution_completion_rate'] / 100
        )
      ],
      'average_contribution' => [
        'user_average' => $userPerformance['average_contribution'],
        'group_average' => $groupBenchmarks['average_contribution'],
        'comparison' => $this->calculatePerformanceComparison(
          $userPerformance['average_contribution'],
          $groupBenchmarks['average_contribution']
        )
      ]
    ];
  }

  private function calculatePerformanceComparison(float $userValue, float $groupValue): string
  {
    $difference = $userValue - $groupValue;
    $percentageDifference = abs($difference / $groupValue) * 100;

    return match (true) {
      $percentageDifference < 10 => 'on_par',
      $difference > 0 => 'above_average',
      $difference < 0 => 'below_average',
      default => 'neutral'
    };
  }

  // Contribution Incentive and Reward System
  public function calculateContributionRewards(User $user, Group $group)
  {
    $userContributions = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->where('status', 'paid')
      ->get();

    $rewardPoints = $this->calculateRewardPoints($userContributions);

    return [
      'total_reward_points' => $rewardPoints,
      'reward_tier' => $this->determineRewardTier($rewardPoints),
      'next_tier_progress' => $this->calculateNextTierProgress($rewardPoints)
    ];
  }

  private function calculateRewardPoints(Collection $contributions): int
  {
    return $contributions->reduce(function ($points, $contribution) {
      // Base points for each contribution
      $basePoints = 10;

      // Bonus points for early or consistent contributions
      $bonusPoints = $this->calculateContributionBonusPoints($contribution);

      // Consecutive contribution bonus
      $consecutiveBonus = $this->calculateConsecutiveContributionBonus($contribution);

      return $points + $basePoints + $bonusPoints + $consecutiveBonus;
    }, 0);
  }

  private function calculateContributionBonusPoints(Contribution $contribution): int
  {
    // Early contribution bonus
    $daysEarly = $contribution->contribution_date->diffInDays(
      $contribution->group->expected_contribution_date,
      false
    );

    return match (true) {
      $daysEarly > 7 => 5,   // Early by more than a week
      $daysEarly > 3 => 3,   // Early by 3-7 days
      $daysEarly > 0 => 1,   // Slightly early
      default => 0
    };
  }

  private function calculateConsecutiveContributionBonus(Contribution $contribution): int
  {
    $consecutiveContributions = Contribution::where('user_id', $contribution->user_id)
      ->where('group_id', $contribution->group_id)
      ->where('status', 'paid')
      ->orderBy('contribution_date', 'desc')
      ->take(5)
      ->get();

    $consecutiveCount = $consecutiveContributions->count();

    return match (true) {
      $consecutiveCount >= 5 => 10,  // 5+ consecutive contributions
      $consecutiveCount >= 3 => 5,   // 3-4 consecutive contributions
      $consecutiveCount >= 2 => 2,   // 2 consecutive contributions
      default => 0
    };
  }

  private function determineRewardTier(int $rewardPoints): ?RewardTier
  {
    return RewardTier::where('minimum_points', '<=', $rewardPoints)
      ->orderBy('minimum_points', 'desc')
      ->first();
  }

  private function calculateNextTierProgress(int $rewardPoints): array
  {
    $currentTier = $this->determineRewardTier($rewardPoints);

    if (!$currentTier) {
      $nextTier = RewardTier::orderBy('minimum_points')->first();
      return [
        'points_to_next_tier' => $nextTier->minimum_points,
        'progress_percentage' => 0
      ];
    }

    $nextTier = RewardTier::where('minimum_points', '>', $currentTier->minimum_points)
      ->orderBy('minimum_points')
      ->first();

    if (!$nextTier) {
      return [
        'points_to_next_tier' => 0,
        'progress_percentage' => 100
      ];
    }

    $pointsToNextTier = $nextTier->minimum_points - $rewardPoints;
    $tierPointRange = $nextTier->minimum_points - $currentTier->minimum_points;

    return [
      'points_to_next_tier' => $pointsToNextTier,
      'progress_percentage' => min(100,
        ($rewardPoints - $currentTier->minimum_points) / $tierPointRange * 100
      )
    ];
  }

  // Contribution Risk Prediction Model
  public function predictContributionRisk(User $user, Group $group)
  {
    $contributions = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->get();

    $riskFactors = $this->calculateContributionRiskFactors($contributions);

    return [
      'risk_score' => $this->calculateRiskScore($riskFactors),
      'risk_factors' => $riskFactors,
      'recommended_actions' => $this->generateRiskMitigationActions($riskFactors)
    ];
  }

  private function calculateContributionRiskFactors(Collection $contributions)
  {
    return [
      'overdue_rate' => $this->calculateOverdueRate($contributions),
      'contribution_consistency' => $this->calculateContributionConsistency($contributions),
      'contribution_amount_volatility' => $this->calculateContributionAmountVolatility($contributions),
      'recent_contribution_pattern' => $this->analyzeRecentContributionPattern($contributions)
    ];
  }

  private function calculateContributionConsistency(Collection $contributions): float
  {
    if ($contributions->isEmpty()) {
      return 0;
    }

    $paidContributions = $contributions->filter(fn($c) => $c->status === 'paid');
    return $paidContributions->count() / $contributions->count();
  }

  private function calculateContributionAmountVolatility(Collection $contributions): float
  {
    if ($contributions->isEmpty()) {
      return 0;
    }

    $amounts = $contributions->pluck('amount');
    $mean = $amounts->avg();

    $variance = $amounts->reduce(function ($carry, $amount) use ($mean) {
        return $carry + pow($amount - $mean, 2);
      }, 0) / $amounts->count();

    return sqrt($variance);
  }

  private function analyzeRecentContributionPattern(Collection $contributions)
  {
    $recentContributions = $contributions->sortByDesc('contribution_date')->take(3);

    if ($recentContributions->isEmpty()) {
      return 'no_recent_contributions';
    }

    $statusPattern = $recentContributions->pluck('status')->unique();

    return match (true) {
      $statusPattern->contains('overdue') => 'inconsistent',
      $statusPattern->count() === 1 && $statusPattern->first() === 'paid' => 'consistent',
      default => 'variable'
    };
  }

  private function calculateRiskScore(array $riskFactors): float
  {
    // Weighted risk calculation
    $weightedRiskScore = (
      ($riskFactors['overdue_rate'] * 0.4) +
      ((1 - $riskFactors['contribution_consistency']) * 0.3) +
      ($riskFactors['contribution_amount_volatility'] / 100 * 0.2) +
      ($this->mapRecentPatternToRisk($riskFactors['recent_contribution_pattern']) * 0.1)
    );

    return min(1, max(0, $weightedRiskScore));
  }

  private function mapRecentPatternToRisk(string $pattern): float
  {
    return match ($pattern) {
      'inconsistent' => 0.8,
      'variable' => 0.5,
      'consistent' => 0.2,
      default => 0.5
    };
  }

  private function generateRiskMitigationActions(array $riskFactors): array
  {
    $actions = [];

    if ($riskFactors['overdue_rate'] > 0.3) {
      $actions[] = 'Set up automatic contributions';
      $actions[] = 'Create a dedicated savings account for group contributions';
    }

    if ($riskFactors['contribution_consistency'] < 0.6) {
      $actions[] = 'Schedule monthly contribution reminders';
      $actions[] = 'Review personal budget and contribution strategy';
    }

    if ($riskFactors['contribution_amount_volatility'] > 50) {
      $actions[] = 'Stabilize contribution amounts';
      $actions[] = 'Consult financial advisor for consistent contribution planning';
    }

    match ($riskFactors['recent_contribution_pattern']) {
      'inconsistent' => $actions[] = 'Develop a structured contribution plan',
      'variable' => $actions[] = 'Create a consistent contribution schedule',
      default => null
    };

    return $actions;
  }

  // Contribution Forecasting and Predictive Analytics
  public function forecastGroupContributionTrajectory(Group $group, int $projectionMonths = 12)
  {
    $historicalContributions = Contribution::where('group_id', $group->id)
      ->select(
        DB::raw('MONTH(contribution_date) as month'),
        DB::raw('YEAR(contribution_date) as year'),
        DB::raw('SUM(amount) as total_amount'),
        DB::raw('COUNT(*) as contribution_count')
      )
      ->groupBy('year', 'month')
      ->orderBy('year', 'desc')
      ->orderBy('month', 'desc')
      ->limit(12)
      ->get();

    $timeSeriesAnalysis = $this->performTimeSeriesAnalysis($historicalContributions);
    $seasonalityFactors = $this->identifySeasonalityFactors($historicalContributions);

    return [
      'historical_trend' => $historicalContributions,
      'projection' => $this->generateContributionProjection(
        $historicalContributions,
        $projectionMonths
      ),
      'time_series_analysis' => $timeSeriesAnalysis,
      'seasonality_factors' => $seasonalityFactors,
      'risk_assessment' => $this->assessProjectionRisk($timeSeriesAnalysis)
    ];
  }

  private function performTimeSeriesAnalysis(Collection $historicalData)
  {
    // Simple moving average calculation
    $movingAverage = $this->calculateMovingAverage($historicalData);

    // Trend identification
    $trend = $this->identifyTrend($historicalData);

    // Volatility calculation
    $volatility = $this->calculateVolatility($historicalData);

    return [
      'moving_average' => $movingAverage,
      'trend' => $trend,
      'volatility' => $volatility
    ];
  }

  private function calculateMovingAverage(Collection $data, int $periods = 3)
  {
    return $data->sliding($periods)->map(function ($window) {
      return $window->avg('total_amount');
    });
  }

  private function identifyTrend(Collection $data)
  {
    $trendLine = $this->calculateTrendLine($data);

    return match (true) {
      $trendLine > 0 => 'increasing',
      $trendLine < 0 => 'decreasing',
      default => 'stable'
    };
  }

  private function calculateTrendLine(Collection $data)
  {
    if ($data->isEmpty()) {
      return 0;
    }

    $firstPoint = $data->first()->total_amount;
    $lastPoint = $data->last()->total_amount;

    return ($lastPoint - $firstPoint) / $data->count();
  }

  private function calculateVolatility(Collection $data)
  {
    $amounts = $data->pluck('total_amount');

    $mean = $amounts->avg();
    $variance = $amounts->reduce(function ($carry, $amount) use ($mean) {
        return $carry + pow($amount - $mean, 2);
      }, 0) / $amounts->count();

    return sqrt($variance);
  }

  private function identifySeasonalityFactors(Collection $historicalContributions)
  {
    $monthlyContributions = $historicalContributions->groupBy('month');

    return $monthlyContributions->map(function ($monthData) {
      return [
        'month' => $monthData->first()->month,
        'average_contribution' => $monthData->avg('total_amount'),
        'contribution_count' => $monthData->sum('contribution_count')
      ];
    })->sortByDesc('average_contribution');
  }

  private function generateContributionProjection(
    Collection $historicalData,
    int        $projectionMonths
  )
  {
    $lastDataPoint = $historicalData->first();
    $movingAverage = $this->calculateMovingAverage($historicalData);
    $averageGrowth = $this->calculateAverageGrowth($historicalData);

    $projections = collect();
    $currentAmount = $lastDataPoint->total_amount;

    for ($i = 0; $i < $projectionMonths; $i++) {
      // Apply moving average and growth rate
      $projectedAmount = $currentAmount * (1 + $averageGrowth);

      $projections->push([
        'month' => now()->addMonths($i)->format('Y-m'),
        'projected_amount' => $projectedAmount,
        'confidence_interval' => $this->calculateConfidenceInterval($projectedAmount)
      ]);

      $currentAmount = $projectedAmount;
    }

    return $projections;
  }

  private function calculateAverageGrowth(Collection $historicalData)
  {
    $growthRates = $historicalData->sliding(2)->map(function ($window) {
      $previous = $window->first()->total_amount;
      $current = $window->last()->total_amount;

      return $previous > 0
        ? ($current - $previous) / $previous
        : 0;
    });

    return $growthRates->avg();
  }

  private function calculateConfidenceInterval(float $projectedAmount)
  {
    // Simple confidence interval calculation
    $confidenceLevel = 0.95; // 95% confidence interval
    $standardDeviation = $projectedAmount * 0.1; // Assume 10% standard deviation

    $marginOfError = $standardDeviation * 1.96; // z-score for 95% confidence

    return [
      'lower_bound' => $projectedAmount - $marginOfError,
      'upper_bound' => $projectedAmount + $marginOfError
    ];
  }

  private function assessProjectionRisk(array $timeSeriesAnalysis)
  {
    $riskScore = 0;

    // Trend risk
    $riskScore += match ($timeSeriesAnalysis['trend']) {
      'decreasing' => 0.7,
      'stable' => 0.3,
      'increasing' => 0.1
    };

    // Volatility risk
    $riskScore += match (true) {
      $timeSeriesAnalysis['volatility'] > 100 => 0.6,
      $timeSeriesAnalysis['volatility'] > 50 => 0.4,
      default => 0.2
    };

    return min(1, max(0, $riskScore));
  }
}

