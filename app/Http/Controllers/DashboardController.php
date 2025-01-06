<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Group;
use App\Models\Contribution;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
  /**
   * Get the middleware that should be assigned to the controller.
   */
  public static function middleware(): array
  {
    return [
      new Middleware('permission:view dashboard'),
    ];
  }

  /**
   * Show the application dashboard.
   *
   * @return \Inertia\Response
   */
  public function index(): Response
  {
    $user = Auth::user(); // 'permission:view dashboard'

    // Fetch user's groups
    $groups = $user->hasRole('super-admin')
      ? Group::with('members')->get()
      : $user->groups()->with('members')->get();

    // Dashboard statistics
    $stats = $this->getDashboardStatistics($user);

    // Recent activities
    $recentActivities = $this->getRecentActivities($user);

    // Upcoming contributions
    $upcomingContributions = $this->getUpcomingContributions($user);

    // Savings progress data
    $savingsProgressData = $this->getSavingsProgressData($user);

    return Inertia::render('Dashboard', [
      'groups' => $groups,
      'stats' => $stats,
      'recentActivities' => $recentActivities,
      'upcomingContributions' => $upcomingContributions,
      'savingsProgressData' => $savingsProgressData
    ]);
  }

  /**
   * Get dashboard statistics for the user
   *
   * @param User $user
   * @return array
   */
  private function getDashboardStatistics(User $user): array
  {
    // If super admin, get global stats
    if ($user->hasRole('super-admin')) {
      return [
        'totalGroups' => Group::count(),
        'totalMembers' => User::count(),
        'totalSavings' => Contribution::sum('amount'),
        'totalLoans' => Loan::sum('amount')
      ];
    }

    // For regular users, get personal stats
    $userGroups = $user->groups;

    return [
      'totalGroups' => $userGroups->count(),
      'totalSavings' => Contribution::whereHas('groupMember', function ($query) use ($user) {
        $query->where('user_id', $user->id);
      })->sum('amount'),
      'activeLoans' => Loan::whereHas('groupMember', function ($query) use ($user) {
        $query->where('user_id', $user->id)
          ->where('status', 'active');
      })->count(),
      'nextContributionDate' => $this->getNextContributionDate($user)
    ];
  }

  /**
   * Get recent user activities
   *
   * @param User $user
   * @return array
   */
  private function getRecentActivities(User $user): array
  {
    $activities = collect();

    // Contributions
    $contributions = Contribution::whereHas('groupMember', function ($query) use ($user) {
      $query->where('user_id', $user->id);
    })
      ->latest()
      ->limit(10)
      ->get()
      ->map(function ($contribution) {
        return [
          'type' => 'contribution',
          'title' => 'Contribution Made',
          'description' => "Contributed {$contribution->amount} to {$contribution->groupMember->group->name}",
          'timestamp' => $contribution->created_at
        ];
      });

    // Loans
    $loans = Loan::whereHas('groupMember', function ($query) use ($user) {
      $query->where('user_id', $user->id);
    })
      ->latest()
      ->limit(10)
      ->get()
      ->map(function ($loan) {
        return [
          'type' => 'loan',
          'title' => 'Loan Transaction',
          'description' => "Loan of {$loan->amount} from {$loan->groupMember->group->name}",
          'timestamp' => $loan->created_at
        ];
      });

    return $activities->merge($contributions)
      ->merge($loans)
      ->sortByDesc('timestamp')
      ->values()
      ->all();
  }

  /**
   * Get upcoming contributions
   *
   * @param User $user
   * @return array
   */
  private function getUpcomingContributions(User $user): array
  {
    return $user->groups->flatMap(function ($group) {
      return $group->members
        ->filter(fn($member) => $member->user_id === Auth::id())
        ->map(function ($member) {
          // Logic to determine upcoming contributions
          return [
            'group' => $member->group,
            'amount' => $member->group->contribution_amount,
            'dueDate' => $this->calculateNextContributionDate($member->group)
          ];
        });
    })->toArray();
  }

  /**
   * Get savings progress data
   *
   * @param User $user
   * @return array
   */
  private function getSavingsProgressData(User $user): array
  {
    return $user->groups->map(function ($group) use ($user) {
      $contributions = Contribution::whereHas('groupMember', function ($query) use ($user, $group) {
        $query->where('user_id', $user->id)
          ->where('group_id', $group->id);
      })->get();

      return [
        'groupName' => $group->name,
        'totalContributions' => $contributions->sum('amount'),
        'contributionCount' => $contributions->count()
      ];
    })->toArray();
  }

  /**
   * Calculate next contribution date
   *
   * @param Group $group
   * @return Carbon
   */
  private function calculateNextContributionDate(Group $group): Carbon
  {
    // Implement logic based on group's contribution frequency
    $lastContribution = $group->contributions()
      ->latest()
      ->first();

    return match ($group->contribution_frequency) {
      'monthly' => $lastContribution
        ? $lastContribution->created_at->addMonth()
        : now()->addMonth(),
      'quarterly' => $lastContribution
        ? $lastContribution->created_at->addQuarters(1)
        : now()->addQuarters(1),
      'weekly' => $lastContribution
        ? $lastContribution->created_at->addWeek()
        : now()->addWeek(),
      default => now()->addMonth()
    };
  }

  /**
   * Get next contribution date
   *
   * @param User $user
   * @return ?Carbon
   */
  private function getNextContributionDate(User $user): ?Carbon
  {
    $nextContribution = $user->groups
      ->map(fn($group) => $this->calculateNextContributionDate($group))
      ->min();

    return $nextContribution;
  }
}
