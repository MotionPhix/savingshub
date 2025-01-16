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

  public function index(): Response
  {
    $user = Auth::user();

    // Super admin dashboard
    if ($user->hasRole('super_admin')) {
      return $this->superAdminDashboard();
    }

    // Get groups user is a member of with detailed relationships
    $groups = $user->groups()->with([
      'members' => function ($query) use ($user) {
        $query->where('user_id', $user->id);
      },
      'contributions',
      'loans'
    ])->withCount([
      'members',
      'contributions',
      'loans'
    ])->get();

    // Determine user's roles across groups
    $userGroupRoles = $groups->mapWithKeys(function ($group) use ($user) {
      $membership = $group->members->first(fn($member) => $member->user_id === $user->id);
      return [$group->id => $membership ? $membership->role : null];
    });

    // Specialized dashboard data based on user's most prominent role
    $dashboardData = $this->getDashboardDataByRole($user, $groups, $userGroupRoles);

    return Inertia::render('Dashboard/Home', [
      'user' => $user,
      'groups' => $groups,
      'userGroupRoles' => $userGroupRoles,
      'activeGroupRole' => fn() => Group::findOrFail(session('active_group_id'))
        ->members()
        ->where('user_id', Auth::id())
        ->value('role') ?? 'member',
      'dashboardData' => $dashboardData,
      'analytics' => $this->getUserAnalytics($user, $groups)
    ]);
  }

  protected function getDashboardDataByRole(User $user, $groups, $userGroupRoles)
  {
    // Determine the most significant role
    $prominentRole = $this->determineProminentRole($userGroupRoles);

    return match ($prominentRole) {
      4 => $this->getGroupAdminDashboardData($user, $groups),
      3 => $this->getTreasurerDashboardData($user, $groups),
      2 => $this->getSecretaryDashboardData($user, $groups),
      default => $this->getMemberDashboardData($user, $groups)
    };
  }

  protected function determineProminentRole($userGroupRoles)
  {
    $rolePriority = [
      'admin' => 4,
      'treasurer' => 3,
      'secretary' => 2,
      'member' => 1
    ];

    return $userGroupRoles->max(function ($role) use ($rolePriority) {
      return $rolePriority[$role] ?? 0;
    });
  }

  protected function getGroupAdminDashboardData(User $user, $groups)
  {
    return [
      'managedGroups' => $groups->filter(function ($group) use ($user) {
        return $group->members->first()->role === 'admin';
      })->map(function ($group) {
        return [
          'id' => $group->id,
          'name' => $group->name,
          'pendingMembers' => $group->members()->where('group_members.status', 'pending')->count(),
          'pendingLoans' => $group->loans()
            ->join('group_members as gm', 'loans.group_member_id', '=', 'gm.id')
            ->where('gm.group_id', $group->id)
            ->where('loans.status', 'pending')
            ->count(),
          'pendingContributions' => $group->contributions()
            ->join('group_members as gm', 'contributions.group_member_id', '=', 'gm.id')
            ->where('gm.group_id', $group->id)
            ->where('contributions.status', 'pending')
            ->count(),
        ];
      }),
      'totalManagedGroups' => $groups->filter(function ($group) use ($user) {
        return $group->members->first()->role === 'admin';
      })->count()
    ];
  }

  protected function getTreasurerDashboardData(User $user, $groups)
  {
    return [
      'financialOverview' => $groups->map(function ($group) {
        return [
          'groupName' => $group->name,
          'totalContributions' => $group->contributions()->sum('amount'),
          'totalLoans' => $group->loans()->sum('total_amount'),
          'availableBalance' => $group->contributions()->sum('amount') - $group->loans()->sum('total_amount')
        ];
      }),
      'upcomingFinancialActivities' => $this->getUpcomingFinancialActivities($groups)
    ];
  }

  protected function getSecretaryDashboardData(User $user, $groups)
  {
    return [
      'groupManagement' => $groups->map(function ($group) {
        return [
          'groupName' => $group->name,
          'pendingInvitations' => $group->invitations()->where('status', 'pending')->count(),
          'upcomingMeetings' => $group->meetings()->upcoming()->get()
        ];
      }),
      'communicationTasks' => $this->getCommunicationTasks($groups)
    ];
  }

  protected function getMemberDashboardData(User $user, $groups)
  {
    return [
      'personalContributions' => $groups->map(function ($group) use ($user) {
        $memberContributions = $group->contributions()
          ->whereHas('groupMember', function ($query) use ($user) {
            $query->where('user_id', $user->id);
          });

        return [
          'groupName' => $group->name,
          'totalContributed' => $memberContributions->sum('amount'),
          'contributionCount' => $memberContributions->count()
        ];
      }),
      'loanStatus' => $this->getUserLoanStatus($user, $groups)
    ];
  }

  protected function getUpcomingFinancialActivities($groups)
  {
    // Implement logic to fetch upcoming financial activities
    return $groups->flatMap(function ($group) {
      return [
        'pendingLoans' => $group->loans()->where('status', 'pending')->get(),
        'upcomingContributions' => $group->contributions()->upcoming()->get()
      ];
    });
  }

  protected function getCommunicationTasks($groups)
  {
    // Implement communication-related tasks for secretary
    return $groups->flatMap(function ($group) {
      return [
        'pendingInvitations' => $group->invitations()->where('status', 'pending')->get(),
        'upcomingMeetings' => $group->meetings()->upcoming()->get()
      ];
    });
  }

  protected function getUserLoanStatus(User $user, $groups)
  {
    return $groups->map(function ($group) use ($user) {
      $activeLoan = $group->loans()
        ->whereHas('groupMember', function ($query) use ($user) {
          $query->where('user_id', $user->id);
        })
        ->where('loans.status', 'active')
        ->first();

      return [
        'groupName' => $group->name,
        'hasActiveLoan' => $activeLoan !== null,
        'loanDetails' => $activeLoan ? [
          'amount' => $activeLoan->total_amount,
          'remainingBalance' => $activeLoan->remaining_balance,
          'status' => $activeLoan->status
        ] : null
      ];
    });
  }

  // from here down, keep checking

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

  protected function superAdminDashboard()
  {
    return Inertia::render('Dashboard/SuperAdminDashboard', [
      'totalUsers' => $this->getTotalUsers(),
      'totalGroups' => $this->getTotalGroups(),
      'totalContributions' => $this->getTotalContributions(),
      'totalLoans' => $this->getTotalLoans(),
      'recentActivity' => $this->getRecentSystemActivity()
    ]);
  }

  protected function getUserAnalytics($user, $groups)
  {
    return [
      'total_groups' => $groups->count(),
      'owned_groups' => $groups->filter(function ($group) use ($user) {
        return $group->members->first()->role === 'admin';
      })->count(),
      'total_contributions' => Contribution::whereIn('group_id', $groups->pluck('id'))
        ->where('user_id', $user->id)
        ->sum('amount'),
      'total_loans' => Loan::whereIn('group_id', $groups->pluck('id'))
        ->where('user_id', $user->id)
        ->sum('total_amount'),
      'recent_activities' => $this->getUserRecentActivities($user, $groups)
    ];
  }

  public function selectActiveGroup(Group $group)
  {
    // Verify user is a member of the group
    $membership = $group->members()
      ->where('user_id', Auth::id())
      ->first();

    if (!$membership) {
      return redirect()->back()->with('error', 'You are not a member of this group');
    }

    // Store selected group in session
    session(['active_group_id' => $group->id]);

    // Redirect to group dashboard
    return redirect()->route('groups.dashboard', $group);
  }

  protected function getTotalUsers()
  {
    return User::count();
  }

  protected function getTotalGroups()
  {
    return Group::count();
  }

  protected function getTotalContributions()
  {
    return Contribution::sum('amount');
  }

  protected function getTotalLoans()
  {
    return Loan::sum('total_amount');
  }

  protected function getRecentSystemActivity()
  {
    // Combine recent groups, contributions, and loans
    $recentGroups = Group::latest()->take(5)->get();
    $recentContributions = Contribution::with('group', 'user')->latest()->take(5)->get();
    $recentLoans = Loan::with('group', 'user')->latest()->take(5)->get();

    return [
      'groups' => $recentGroups,
      'contributions' => $recentContributions,
      'loans' => $recentLoans
    ];
  }

  protected function getUserRecentActivities($user, $groups)
  {
    // Combine and sort recent contributions and loans
    $contributions = Contribution::whereIn('group_id', $groups->pluck('id'))
      ->where('user_id', $user->id)
      ->latest()
      ->take(5)
      ->get()
      ->map(function ($contribution) {
        return [
          'type' => 'contribution',
          'amount' => $contribution->amount,
          'group_name' => $contribution->group->name,
          'date' => $contribution->created_at
        ];
      });

    $loans = Loan::whereIn('group_id', $groups->pluck('id'))
      ->where('user_id', $user->id)
      ->latest()
      ->take(5)
      ->get()
      ->map(function ($loan) {
        return [
          'type' => 'loan',
          'amount' => $loan->total_amount,
          'group_name' => $loan->group->name,
          'date' => $loan->created_at
        ];
      });

    return $contributions->merge($loans)
      ->sortByDesc('date')
      ->values()
      ->all();
  }
}
