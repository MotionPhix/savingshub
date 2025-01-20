<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\GroupMember;
use App\Models\Contribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContributionService
{
  public function __construct(
    protected GroupActivityService $activityService,
    protected penaltyCalculationService $penaltyCalculationService
  ) {}

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
      ')->first();

    $memberContributionBreakdown = GroupMember::where('group_id', $group->id)
      ->with(['contributions' => function ($query) {
        $query->selectRaw('
          group_member_id,
          COUNT(*) as total_contributions,
          SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_contributions,
          SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_contributions
        ')->groupBy('group_member_id');
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

  // real life situations
  public function validateContributionState(GroupMember $groupMember, Group $group): bool
  {
    $lastContribution = $groupMember->contributions()
      ->where('group_id', $group->id)
      ->latest()
      ->first();

    // Ensure the last contribution is verified before allowing another contribution
    if ($lastContribution && !$lastContribution->is_verified) {
       throw new \Exception('Previous contribution is not verified. Please wait for verification before making another contribution.');
    }

    // Ensure no overdue payments or balance due
    if ($lastContribution && $lastContribution->status === 'partial') {
      throw new \Exception('Previous contribution is partial. Settle the balance before making a new contribution.');
    }

    return true;
  }

  private function checkForLatePayments($group, $contributionDate, $amount)
  {
    // Get the first day of the next month
    $nextMonthStart = now()->addMonth()->startOfMonth();

    // Calculate the cutoff date by adding the 'allow_payments_until' days to the start of the next month
    $paymentCutoffDate = $nextMonthStart->addDays($group->allow_contributions_until);

    // If the contribution date is later than the cutoff date, apply penalties
    if ($contributionDate->gt($paymentCutoffDate)) {

      // Fetch the user's previous total contribution balance (including partial payments)
      $totalPaidSoFar = GroupActivity::where('group_id', $group->id)
        ->where('user_id', Auth::user()->id)
        ->whereIn('type', ['partial_contribution_made', 'contribution_made'])
        ->sum('metadata->amount'); // Sum all previous payments

      // Calculate the remaining balance (the user was supposed to pay the full required amount)
      $remainingBalance = $group->contribution_amount - $totalPaidSoFar;

      // Apply penalty if there's a balance remaining
      $penalty = ($remainingBalance * $group->penalty_fee_percentage);
      $totalDue = $remainingBalance + $penalty;

      // If the user is paying less than the required amount (balance + penalty), show an error
      if ($amount < $totalDue) {
        throw new \Exception("The amount you are contributing is less than the overdue balance plus the penalty fee. You must pay the remaining balance of {$remainingBalance} plus the penalty of {$penalty} for a total of {$totalDue}. The amount you are contributing is {$amount} less than the required amount.");
      }
    }
  }

  /**
   * Calculate contribution status
   */
  public function calculateContributionStatus(
    Group $group,
    float $amount,
    User $user,
    string $contributionType
  ): array {
    // Validate the contribution amount
    $validationResult = $this->validateContributionAmount(
      $group,
      $user,
      $amount,
      $contributionType
    );

    // Additional checks for existing contributions in the current month
    $existingContribution = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->whereMonth('contribution_date', now()->month)
      ->whereYear('contribution_date', now()->year)
      ->first();

    // Prevent multiple full contributions in the same month
    if ($existingContribution &&
      $existingContribution->status === 'paid' &&
      $contributionType === 'regular') {
      return [
        'status' => 'failed',
        'message' => 'You have already made a full contribution this month.'
      ];
    }

    return [
      'status' => $validationResult['status'],
      'message' => $validationResult['message'],
      'contribution_details' => $validationResult['contribution_details']
    ];
  }

  public function prepareContributionMetadata(Group $group, array $contributionStatus): array
  {
    $metadata = [
      'group_contribution_amount' => $group->contribution_amount,
      'contribution_frequency' => $group->contribution_frequency,
    ];

    // Add specific metadata based on contribution status
    if (isset($contributionStatus['overpayment_amount'])) {
      $metadata['overpayment_amount'] = $contributionStatus['overpayment_amount'];
    }

    if (isset($contributionStatus['shortfall_amount'])) {
      $metadata['shortfall_amount'] = $contributionStatus['shortfall_amount'];
    }

    return $metadata;
  }

  private function checkPartialPaymentLimit($group, $user)
  {
    // Fetch the number of partial payments already made by the user
    $partialPayments = GroupActivity::where('group_id', $group->id)
      ->where('user_id', $user->id)
      ->where('type', 'partial_contribution_made')
      ->where('metadata->date', now()->timezone($user->timezone)->format('Y-m-d'))
      ->count();

    if ($partialPayments >= $group->max_allowed_partial_contributions) {
      throw new \Exception('You have reached the maximum allowed partial payments.');
    }
  }

  private function checkDuplicateContribution($group, $user, $contributionDate)
  {
    // Check if a contribution for the same date exists and is unverified
    $existingContributionFromActivity = GroupActivity::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->whereIn('type', ['partial_contribution_made', 'contribution_made'])
      ->where('metadata->date', $contributionDate->toDateString()) // Check for duplicate using the exact date in metadata
      ->whereNull('metadata->verified_at') // Ensure it's unverified
      ->exists();

    if ($existingContributionFromActivity) {
      throw new \Exception('A contribution for this date already exists and is not yet verified.');
    }
  }

  private function storeContributionData($group, $user, $validatedData, $contributionDate, $amount, $contributionStatus)
  {
    $membership = GroupMember::where('user_id', $user->id)->where('group_id', $group->id)->first();

    if ($contributionStatus['paid'])

    // Log the contribution in GroupActivity
    GroupActivity::create([
      'group_id' => $group->id,
      'user_id' => $user->id,
      'type' => $amount < $group->contribution_amount ? 'partial_contribution_made' : 'contribution_made',
      'description' => 'User made a contribution.',
      'metadata' => [
        'amount' => $amount,
        'date' => $contributionDate,
        'verified_at' => null,
        'group_membership' => $membership,
        'group' => $group->name,
        'contribution_amount' => $validatedData['amount'], // If needed for record
      ],
    ]);

    // If the contribution is full, update the contributions table
    if ($amount >= $group->contribution_amount) {
      Contribution::create([
        'group_id' => $group->id,
        'group_member_id' => $membership->id,
        'user_id' => $user->id,
        'amount' => $amount,
        'contribution_date' => $contributionDate,
        'status' => $contributionStatus['status'],
      ]);
    } else {

      $contribution = Contribution::create([
        'group_member_id' => $membership->id,
        'group_id' => $group->id,
        'user_id' => Auth::id(),
        'amount' => $validatedData['amount'],
        'contribution_date' => $validatedData['contribution_date'],
        'status' => $contributionStatus['status'],
        'type' => $validatedData['type'] ?? 'regular',
        'payment_method' => $validatedData['payment_method'] ?? null,
        'transaction_reference' => $validatedData['transaction_reference'] ?? null,
        'is_verified' => false, // Always start as unverified
        'metadata' => $this->prepareContributionMetadata($group, $contributionStatus)
      ]);

    }
    // Log the contribution in GroupActivity
    $this->activityService->log(
      $group,
      $validatedData['amount'] < $group->contribution_amount ? 'partial_contribution_made' : 'contribution_made',
      $user,
      null,
      [
        'amount' => $validatedData['amount'],
        'date' => $validatedData['contribution_date'],
        'required_amount' => $group->getAttribute('contribution_amount'),
      ],
    );

    return $contribution;
  }

  /**
   * @throws \Exception
   */
  public function storeContribution(array $validatedData, Group $group, User $user, array $contributionStatus)
  {
    // Check if penalty is required
    $requiresPenalty = $this->penaltyCalculationService->requiresPenalty($group);

    // If penalty is required, adjust the contribution amount
    if ($requiresPenalty) {
      $penaltyDetails = $this->penaltyCalculationService->calculatePenalty(
        $group,
        null,
        $validatedData['amount']
      );

      // Ensure the contribution covers the penalty
      $validatedData['amount'] += $penaltyDetails['total_penalty'];
    }

    // Extract and validate the date and amount
    $contributionDate = Carbon::parse($validatedData['contribution_date']);
    $amount = $validatedData['amount'];
    $requiredAmount = $group->getAttribute('contribution_amount');

    // Ensure the contribution is within the allowed period
    $this->checkForLatePayments($group, $contributionDate, $amount);

    // Check if the user has exceeded the maximum allowed partial payments
    $this->checkPartialPaymentLimit($group, $user, $validatedData);

    // Check if a contribution for this date already exists
    $this->checkDuplicateContribution($group, $user, $contributionDate);

    // If the contribution type is 'penalty', add penalty fee
    if ($validatedData['type'] === 'penalty') {
      $penaltyFee = $this->calculatePenaltyFee($amount, $group);
      $amount += $penaltyFee; // Add penalty fee to the contribution amount
    }

    // Store the contribution if everything is valid
    return $this->storeContributionData(
      $group, $user, $validatedData, $contributionDate, $amount, $contributionStatus
    );
  }

  private function calculatePenaltyFee(float $amount, $group): float
  {
    // Define your penalty calculation logic here
    $penaltyRate = $group->penalty_fee_percentage; // Default is 5% (0.05)
    return bcmul($amount, $penaltyRate, 2);
  }

  /**
   * Determine if a contribution is overdue
   */
  public function isContributionOverdue(Group $group, Carbon $contributionDate): bool
  {
    // Determine the contribution period
    $currentMonth = $contributionDate->month;
    $currentYear = $contributionDate->year;

    // Calculate the contribution deadline
    $contributionDeadline = $contributionDate->endOfMonth()->endOfDay();

    // Calculate the next month's tolerance deadline
    $toleranceDeadline = Carbon::create($currentYear, $currentMonth, $group->allow_contributions_until)
      ->addMonth()
      ->endOfDay();

    // Check if the contribution is beyond the current month's deadline
    return now()->isAfter($contributionDeadline) && now()->isBefore($toleranceDeadline);
  }

  /**
   * Calculate remaining balance and penalty for a contribution
   */
  public function calculateContributionDetails(Group $group, User $user): array
  {
    // Find the most recent contribution for this group and user
    $latestContribution = Contribution::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->orderByDesc('contribution_date')
      ->first();

    // Default contribution amount
    $requiredAmount = $group->contribution_amount;
    $remainingBalance = $requiredAmount;
    $penaltyFee = 0;
    $isOverdue = false;

    // If there's a previous contribution
    if ($latestContribution) {
      // Check if there's a remaining balance from previous partial payment
      if ($latestContribution->status === 'partial') {
        $remainingBalance = bcsub($requiredAmount, $latestContribution->amount, 2);
      }

      // Check if the contribution is overdue
      $isOverdue = $this->isContributionOverdue($group, $latestContribution->contribution_date);
    }

    // Calculate penalty if overdue
    if ($isOverdue) {
      $penaltyFee = $this->calculatePenaltyFee($requiredAmount, $group);
      $remainingBalance = bcadd($remainingBalance, $penaltyFee, 2);
    }

    // Check if penalty is required
    $penaltyDetails = $this->penaltyCalculationService->calculatePenalty(
      $group,
      $latestContribution,
      $group->contribution_amount
    );

    return [
      'required_amount' => $requiredAmount,
      'remaining_balance' => $remainingBalance,
      'penalty_fee' => $penaltyFee,
      'is_overdue' => $isOverdue,
      'penalty_details' => $penaltyDetails
    ];
  }

  /**
   * Validate contribution amount
   */
  public function validateContributionAmount(
    Group $group,
    User $user,
    float $enteredAmount,
    string $contributionType
  ): array {
    // Get contribution details
    $contributionDetails = $this->calculateContributionDetails($group, $user);

    // Validate contribution amount
    $isValidAmount = bccomp(
        $enteredAmount,
        $contributionDetails['remaining_balance'],
        2
      ) >= 0;

    // Determine contribution status
    $status = match(true) {
      $isValidAmount && $enteredAmount == $contributionDetails['remaining_balance'] => 'paid',
      $isValidAmount && $enteredAmount > $contributionDetails['remaining_balance'] => 'overpaid',
      !$isValidAmount => 'partial',
      default => 'failed'
    };

    return [
      'is_valid' => $isValidAmount,
      'status' => $status,
      'contribution_details' => $contributionDetails,
      'entered_amount' => $enteredAmount,
      'message' => match($status) {
        'paid' => 'Full contribution received.',
        'overpaid' => 'Overpayment detected. Excess amount will be noted.',
        'partial' => 'Partial contribution received.',
        'failed' => 'Contribution amount is insufficient.',
        default => 'Contribution processing error.'
      }
    ];
  }
}

