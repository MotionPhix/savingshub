<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupSettingsRequest;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupActivityService;
use App\Services\GroupService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class GroupController extends Controller implements HasMiddleware
{
  use AuthorizesRequests;

  public function __construct(
    protected GroupService $groupService,
    protected GroupActivityService $activityService
  ) {}

  public static function middleware(): array
  {
    return [
      new Middleware('can:create groups', only: ['create', 'store']),
      new Middleware('can:edit groups', except: ['edit', 'update']),
      new Middleware('can:delete groups', except: ['destroy']),
      new Middleware('can:view groups', except: ['index', 'show']),
    ];
  }

  public function index()
  {
    $user = auth()->user();

    $groups = $user->groups()
      ->select([
        'groups.id',
        'groups.uuid',
        'groups.name',
        'groups.description',
        'groups.status',
        'groups.created_by',
        'groups.start_date',
        'groups.end_date'
      ])
      ->with([
        'creator:id,name,uuid,email',
        'members' => function($query) {
          $query->where('group_members.status', 'active')
            ->select('group_members.id', 'group_members.group_id', 'group_members.user_id', 'group_members.role');
        }
      ])
      ->withCount([
        'members as total_members' => function($query) {
          $query->where('status', 'active');
        }
      ])
      ->get()
      ->map(function ($group) use ($user) {
        // Manually count pending contributions
        $pendingContributions = DB::table('contributions')
          ->join('group_members', function($join) use ($group) {
            $join->on('contributions.group_member_id', '=', 'group_members.id')
              ->where('group_members.group_id', $group->id);
          })
          ->where('contributions.status', 'pending')
          ->where('group_members.status', 'active')
          ->count();

        // Manually count pending loans
        $pendingLoans = DB::table('loans')
          ->join('group_members', function($join) use ($group) {
            $join->on('loans.group_member_id', '=', 'group_members.id')
              ->where('group_members.group_id', $group->id);
          })
          ->where('loans.status', 'pending')
          ->where('group_members.status', 'active')
          ->count();

        // Determine user's role in the group
        $userMembership = $group->members->first(fn($member) => $member->user_id === $user->id);

        return [
          'id' => $group->id,
          'uuid' => $group->uuid,
          'name' => $group->name,
          'description' => $group->description,
          'status' => $group->status,
          'creator' => [
            'name' => $group->creator->name,
            'avatar' => $group->creator->avatar
          ],
          'total_members' => $group->total_members,
          'user_role' => $userMembership ? $userMembership->role : null,
          'start_date' => $group->start_date,
          'end_date' => $group->end_date,
          'pending_contributions_count' => $pendingContributions,
          'pending_loan_requests_count' => $pendingLoans,
          'can_contribute' => $this->canContribute($group, $user),
          'can_request_loan' => $this->canRequestLoan($group, $user)
        ];
      });

    return Inertia::render('Groups/Index', [
      'groups' => $groups,
    ]);
  }

  public function create()
  {
    $user = Auth::user();

    // Early return if user cannot create more groups
    if (!$user->canCreateGroup()) {
      return redirect()->back()->withErrors([
        'message' => 'You are using the free version. You can only create one group'
      ]);
    }

    return Inertia::render('Groups/Create', [
      'group_types' => $this->getGroupTypes(),
      'contribution_frequencies' => $this->getContributionFrequencies(),
      'loan_interest_types' => [], //$this->getLoanInterestTypes(),
      'existingGroups' => $user->groups()->select('groups.uuid', 'groups.name')->get()
    ]);
  }

  /**
   * Select an active group for the current user
   */
  public function activate(Request $request, Group $group)
  {
    $this->groupService->activateGroup($request, $group);

    // Redirect to the group dashboard or return a response
    return redirect()->intended(route('groups.index'))
      ->with('success', "You've switched to: {$group->name}");
  }

  public function show(Group $group)
  {
    // Authorize group view
    Gate::authorize('view', $group);

    // Optimize eager loading with detailed relationships
    $group->load([
      'creator:id,name,uuid',
      'members' => function ($query) {
        $query->with([
          'user:id,name,uuid',
          'contributions' => function ($contributionQuery) {
            $contributionQuery->select(
              'id',
              'group_member_id',
              'amount',
              'status',
              'contribution_date'
            );
          },
          'loans' => function ($loanQuery) {
            $loanQuery->select(
              'id',
              'group_member_id',
              'principal_amount',
              'total_amount',
              'status',
              'due_date'
            );
          }
        ])
          ->where('status', 'active')
          ->select('id', 'group_id', 'user_id', 'role', 'total_contributions', 'total_loans');
      }
    ]);

    // Aggregate group-level statistics
    $groupStats = $this->calculateGroupStatistics($group);

    // Prepare members with detailed information
    $membersDetails = $this->prepareMembersDetails($group);

    // Recent activities
    $recentActivities = $this->activityService->getRecentActivities($group); // $this->getRecentGroupActivities($group);

    return Inertia::render('Groups/Show', [
      'group' => $group,
      'stats' => $groupStats,
      'members' => $membersDetails,
      'recent_activities' => $recentActivities,
      'canManageGroup' => Gate::allows('update', $group)
    ]);
  }

  public function updateSettings(
    GroupSettingsRequest $request,
    Group $group
  ) {
    // Validate request
    $validatedData = $request->validated();

    // Ensure user has permission
    Gate::authorize('update', $group);

    // Update group settings
    $updatedGroup = $this->groupService->updateSettings(
      $group,
      $validatedData,
      $request->user()
    );

    // Return updated group data
    return back()->with('success', 'Group settings updated successfully');
  }

  protected function calculateGroupStatistics(Group $group): array
  {
    return [
      'total_members' => $group->members->count(),
      'active_members' => $group->members->where('status', 'active')->count(),
      'total_contributions' => DB::table('contributions')
        ->join('group_members', 'contributions.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->where('contributions.status', 'paid')
        ->sum('contributions.amount'),
      'total_loans' => DB::table('loans')
        ->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->whereIn('loans.status', ['active', 'paid'])
        ->sum('loans.total_amount'),
      'pending_contributions' => DB::table('contributions')
        ->join('group_members', 'contributions.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->where('contributions.status', 'pending')
        ->count(),
      'pending_loans' => DB::table('loans')
        ->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->where('loans.status', 'pending')
        ->count(),
      'overdue_contributions' => DB::table('contributions')
        ->join('group_members', 'contributions.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->where('contributions.status', 'overdue')
        ->count(),
      'overdue_loans' => DB::table('loans')
        ->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
        ->where('group_members.group_id', $group->id)
        ->where('loans.status', 'overdue')
        ->count()
    ];
  }

  protected function prepareMembersDetails(Group $group): array
  {
    return $group->members->map(function ($member) {
      return [
        'id' => $member->id,
        'user' => [
          'id' => $member->user->id,
          'name' => $member->user->name,
          'avatar' => $member->user->avatar,
          'uuid' => $member->user->uuid
        ],
        'role' => $member->role,
        'total_contributions' => $member->total_contributions,
        'total_loans' => $member->total_loans,
        'contribution_stats' => [
          'total_paid' => $member->contributions->where('status', 'paid')->sum('amount'),
          'pending_count' => $member->contributions->where('status', 'pending')->count(),
          'overdue_count' => $member->contributions->where('status', 'overdue')->count()
        ],
        'loan_stats' => [
          'total_borrowed' => $member->loans->sum('total_amount'),
          'pending_count' => $member->loans->where('status', 'pending')->count(),
          'active_count' => $member->loans->where('status', 'active')->count(),
          'overdue_count' => $member->loans->where('status', 'overdue')->count()
        ]
      ];
    })->toArray();
  }

  protected function getRecentGroupActivities(Group $group, int $limit = 10)
  {
    // Combine and sort recent contributions and loans
    $contributions = DB::table('contributions')
      ->join('group_members', 'contributions.group_member_id', '=', 'group_members.id')
      ->join('users', 'contributions.user_id', '=', 'users.id')
      ->where('group_members.group_id', $group->id)
      ->select(
        'contributions.id',
        'contributions.amount',
        'contributions.status',
        'contributions.contribution_date as date',
        'users.name as user_name',
        DB::raw("'contribution' as type")
      );

    $loans = DB::table('loans')
      ->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
      ->join('users', 'loans.user_id', '=', 'users.id')
      ->where('group_members.group_id', $group->id)
      ->select(
        'loans.id',
        'loans.total_amount as amount',
        'loans.status',
        'loans.loan_date as date',
        'users.name as user_name',
        DB::raw("'loan' as type")
      );

    return $contributions
      ->union($loans)
      ->orderByDesc('date')
      ->limit($limit)
      ->get();
  }

  public function store(Request $request): RedirectResponse
  {
    // Validate group creation
    $validatedData = $request->validate([
      'name' => 'required|string|max:255|unique:groups,name',
      'start_date' => 'required|date|after:' . now()->addDay()->format('Y-m-d'),
      'end_date' => [
        'required', 'date'
      ],
      'description' => 'nullable|string|max:1000',
      'contribution_frequency' => 'required|in:weekly,monthly,quarterly,annually',
      'contribution_amount' => 'required|numeric|min:1',
      'duration_months' => 'required|integer|min:1|max:36',
      'loan_interest_type' => 'required|in:fixed,variable,tiered',
      'base_interest_rate' => 'required|numeric|min:0|max:100',
      'max_loan_amount' => 'nullable|numeric|min:0',
      'require_group_approval' => 'boolean'
    ]);

    try {
      // Create group using service
      $group = $this->groupService->createGroup(
        $request->user(),
        $validatedData
      );

      return redirect()->route('groups.show', $group->uuid)
        ->with('success', 'Group created successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  // Implement caching for dropdown options
  protected function getGroupTypes(): array
  {
    return cache()->remember('group_types', now()->addDays(30), function () {
      return [
        'savings' => 'Savings Group',
        'investment' => 'Investment Club',
        'loan' => 'Loan Group',
        'social' => 'Social Welfare Group'
      ];
    });
  }

  // Similar caching for other dropdown methods
  protected function getContributionFrequencies(): array
  {
    return cache()->remember('contribution_frequencies', now()->addDays(30), function () {
      return [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annually' => 'Annually'
      ];
    });
  }

  public function showInvite(Group $group) {
    return Inertia::modal('Groups/Partials/InviteMembers', [
      'group' => $group
    ])->baseUrl('/groups');
  }

  // Add rate limiting to sensitive methods
  public function invite(Request $request)
  {
    // Check user's permission to invite members
    $group = Group::findOrFail(session('active_group_id'));
    $this->authorize('invite-members', $group);

    // Validate input with more comprehensive rules
    $validated = $request->validate([
      'emails' => [
        'required',
        'array',
        'min:1',
        'max:10', // Limit to 10 invites at once
        function ($attribute, $value, $fail) {
          // Additional custom validation
          $uniqueEmails = array_unique($value);
          if (count($uniqueEmails) !== count($value)) {
            $fail('Duplicate email addresses are not allowed.');
          }
        }
      ],
      'emails.*' => [
        'required',
        'email:rfc,dns', // More strict email validation
        'not_in:' . auth()->user()->email, // Prevent inviting self
        function ($attribute, $value, $fail) {
          // Check if email is already a member of the group
          $emailIndex = explode('.', $attribute)[1];
          $existingMembership = GroupMember::where('group_id', session('active_group_id'))
            ->whereHas('user', function ($query) use ($value) {
              $query->where('email', $value);
            })
            ->exists();

          if ($existingMembership) {
            $fail("The email {$value} is already a member of this group.");
          }

          // Check if there's a pending invitation
          $existingInvitation = GroupInvitation::where('group_id', session('active_group_id'))
            ->where('email', $value)
            ->where('status', 'pending')
            ->exists();

          if ($existingInvitation) {
            $fail("An invitation has already been sent to {$value}.");
          }
        }
      ],
      'role' => [
        'required',
        'in:member,treasurer,secretary',
        function ($attribute, $value, $fail) use ($group) {
          // Additional role-based authorization check
          if (!auth()->user()->can('assign', [$group, $value])) {
            $fail('You are not authorized to assign this role.');
          }
        }
      ],
      'message' => 'nullable|string|max:500|profane_filter'
    ], [
      'emails.max' => 'You can invite a maximum of 10 members at a time.',
      'emails.*.email' => 'Please provide a valid email address.',
      'emails.*.not_in' => 'You cannot invite yourself to the group.',
    ]);

    // Implement sophisticated rate limiting
    $key = 'group-invite:' . auth()->id() . ':' . session('active_group_id');

    // Allow 5 invitations per hour
    if (RateLimiter::tooManyAttempts($key, 5, 60)) {
      $seconds = RateLimiter::availableIn($key);
      return back()->withErrors([
        'message' => "Too many invitation attempts. Please try again in {$seconds} seconds."
      ]);
    }

    try {
      // Begin database transaction
      DB::beginTransaction();

      // Attempt to send invitations
      $responses = $this->groupService->inviteMembers(
        $validated['emails'],
        $validated['role'],
        $validated['message'] ?? null
      );

      // Increment rate limiter attempts
      RateLimiter::hit($key, 60);

      // Commit transaction
      DB::commit();

      return back()
//        ->with('flush', $responses)
        ->with('flush', 'Invitations sent successfully.');

    } catch (\Exception $e) {
      // Rollback transaction
      DB::rollBack();

      // Log the error
      Log::error('Group Invitation Error: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'group_id' => session('active_group_id'),
        'emails' => $validated['emails']
      ]);

      return back()->withErrors([
        'message' => 'An error occurred while sending invitations. Please try again.'
      ]);
    }
  }

  // Implement soft delete for groups
  public function destroy(Group $group)
  {
    Gate::authorize('delete', $group);

    DB::transaction(function () use ($group) {
      // Soft delete related records
      $group->members()->delete();
      $group->contributions()->delete();
      $group->loans()->delete();

      // Soft delete the group
      $group->delete();
    });

    return redirect()->route('groups.index')
      ->with('success', 'Group deleted successfully.');
  }

  // Helper methods to determine user actions
  protected function canContribute(Group $group): bool
  {
    $membership = $group->members->first(fn($member) => $member->user_id === auth()->id());

    return $membership
      && $membership->status === 'active'
      && $group->status === 'active';
  }

  protected function canRequestLoan(Group $group): bool
  {
    $membership = $group->members->first(fn($member) => $member->user_id === auth()->id());

    return $membership
      && $membership->status === 'active'
      && $group->status === 'active'
      && (!$group->end_date || now()->isBefore($group->end_date));
  }
}
