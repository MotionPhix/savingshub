<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\GroupMember;
use App\Notifications\NewContributionNotification;
use App\Services\ContributionService;
use App\Services\GroupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

class ContributionController extends Controller
{
  public function __construct(
    protected ContributionService $contributionService,
    protected GroupService $groupService
  ) {}

  public function index(Request $request)
  {
    // Get the active group ID from session
    $activeGroupId = session('active_group_id');

    // Fetch the active group
    $activeGroup = Group::findOrFail($activeGroupId);

    // Find the current user's membership in the group
    $currentUserMembership = GroupMember::where('user_id', Auth::id())
      ->where('group_id', $activeGroupId)
      ->first();

    // Determine if the current user has admin/treasurer privileges
    $isAdminOrTreasurer = in_array($currentUserMembership->role, ['admin', 'treasurer']);

    // Base query for contributions
    $query = Contribution::query()
      ->where('group_id', $activeGroupId)
      // If not an admin/treasurer, only show user's own contributions
      ->when(!$isAdminOrTreasurer, function ($q) {
        return $q->where('user_id', Auth::id());
      })
      ->when($request->input('user_id') && $isAdminOrTreasurer, function ($q, $userId) {
        return $q->where('user_id', $userId);
      })
      ->when($request->input('type'), function ($q, $type) {
        return $q->where('type', $type);
      })
      ->when($request->input('status'), function ($q, $status) {
        return $q->where('status', $status);
      });

    // Eager load related models for efficiency
    $contributions = $query
      ->with(['user:id,name,uuid', 'groupMember:id,user_id,role'])
      ->orderByDesc('contribution_date')
      ->paginate(10);

    // Contribution Insights - adjust based on user role
    $contributionInsights = [
      'total_contributed' => $query->clone()->sum('amount'),
      'total_contributions' => $query->clone()->count(),

      'pending_total' => $query->clone()->whereIn('status', ['pending', 'partial'])->sum('amount'),
      'pending_count' => $query->clone()->whereIn('status', ['pending', 'partial'])->count(),

      'paid_total' => $query->clone()->where('status', 'paid')->sum('amount'),
      'paid_count' => $query->clone()->where('status', 'paid')->count(),

      'overdue_total' => $query->clone()->where('status', 'overdue')->sum('amount'),
      'overdue_count' => $query->clone()->where('status', 'overdue')->count(),

      /*'contribution_types' => $query->clone()
        ->groupBy('type')
        ->selectRaw('type, SUM(amount) as total_amount')
        ->pluck('total_amount', 'type')
        ->toArray(),

      'status_breakdown' => $query->clone()
        ->groupBy('status')
        ->selectRaw('status, COUNT(*) as count')
        ->pluck('count', 'status')
        ->toArray()*/

      'contribution_types' => DB::table('contributions')
        ->where('group_id', $activeGroupId)
        ->when(!$isAdminOrTreasurer, function ($q) {
          return $q->where('user_id', Auth::id());
        })
        ->when($request->input('user_id') && $isAdminOrTreasurer, function ($q, $userId) {
          return $q->where('user_id', $userId);
        })
        ->groupBy('type')
        ->select('type', DB::raw('SUM(amount) as total_amount'))
        ->pluck('total_amount', 'type')
        ->toArray(),

      'status_breakdown' => DB::table('contributions')
        ->where('group_id', $activeGroupId)
        ->when(!$isAdminOrTreasurer, function ($q) {
          return $q->where('user_id', Auth::id());
        })
        ->when($request->input('user_id') && $isAdminOrTreasurer, function ($q, $userId) {
          return $q->where('user_id', $userId);
        })
        ->groupBy('status')
        ->select('status', DB::raw('COUNT(*) as count'))
        ->pluck('count', 'status')
        ->toArray(),
    ];

    // If admin/treasurer, fetch additional group financial insights
    $groupFinancialInsights = $isAdminOrTreasurer
      ? $this->groupService->getGroupFinancialInsights($activeGroupId)
      : null;

    // Prepare additional data for admins
    $additionalData = $isAdminOrTreasurer
      ? [
        'group_members' => $activeGroup->members()
          ->with('user:id,name,uuid')
          ->get()
          ->map(function ($member) {
            return [
              'id' => $member->id,
              'user_id' => $member->user_id,
              'name' => $member->user->name,
              'role' => $member->role
            ];
          }),
        'group_financial_insights' => $groupFinancialInsights
      ]
      : [];

    /*// Fetch contributions with filters
    $contributions = Contribution::query()
      ->where('group_id', $activeGroupId)
      ->where('user_id', Auth::id())
      ->when($request->input('type'), function ($query, $type) {
        return $query->where('type', $type);
      })
      ->when($request->input('status'), function ($query, $status) {
        return $query->where('status', $status);
      })
      ->orderByDesc('contribution_date')
      ->paginate(10);

    // Contribution Insights
    $contributionInsights = [
      'total_contributed' => $contributions->sum('amount'),
      'total_contributions' => $contributions->total(),

      'pending_total' => $contributions->whereIn('status', ['pending', 'partial'])->sum('amount'),
      'pending_count' => $contributions->where('status', 'pending')->count(),

      'paid_total' => $contributions->where('status', 'paid')->sum('amount'),
      'paid_count' => $contributions->where('status', 'paid')->count(),

      'overdue_total' => $contributions->where('status', 'overdue')->sum('amount'),
      'overdue_count' => $contributions->where('status', 'overdue')->count(),

      'contribution_types' => $contributions
        ->groupBy('type')
        ->map(fn($group) => $group->sum('amount'))
        ->toArray(),

      'status_breakdown' => $contributions
        ->groupBy('status')
        ->map(fn($group) => $group->count())
        ->toArray()
    ];*/

    return Inertia('Contributions/Index', array_merge([
      'contributions' => $contributions,
      'contributionInsights' => $contributionInsights,
      'isAdminOrTreasurer' => $isAdminOrTreasurer,
    ], $additionalData));

  }

  public function create()
  {
    // Get the active group ID from session
    $activeGroupId = session('active_group_id');

    // Fetch the active group
    $activeGroup = Group::findOrFail($activeGroupId);

    // Check user's membership in the group
    $groupMember = GroupMember::where('user_id', Auth::id())
      ->where('group_id', $activeGroupId)
      ->first();

    // Fetch group members for potential contribution assignment
    $groupMembers = GroupMember::with('user')
      ->where('group_id', $activeGroupId)
      ->get()
      ->map(function ($member) {
        return [
          'id' => $member->id,
          'name' => $member->user->name,
          'email' => $member->user->email
        ];
      });

    // User roles handling
    $userRoles = [];
    if ($groupMember) {
      // If using a roles relationship
      if ($groupMember->roles) {
        $userRoles = $groupMember->roles()->pluck('name')->toArray();
      } else {
        // If using a simple role attribute
        $userRoles = $groupMember->role ? [$groupMember->role] : [];
      }
    }

    // Additional context for contribution creation
    $context = [
      'groupMembers' => $groupMembers,
      'userRoles' => $userRoles,
    ];

    return Inertia('Contributions/Create', $context);
  }

  public function store(Request $request)
  {
    // Get the active group
    $activeGroupId = session('active_group_id');
    $activeGroup = Group::findOrFail($activeGroupId);

    $percentage = $activeGroup->allowed_partial_percentage * 100;
    $total = Number::currency($activeGroup->contribution_amount, in: $activeGroup->settings['currency']);

    // Validate the request
    $validatedData = $request->validate([
      'amount' => [
        'required',
        'numeric',
        function ($attribute, $value, $fail) use ($activeGroup, $percentage, $total) {
          // Calculate the minimum required amount based on the percentage
          $minAmount = bcmul($activeGroup->contribution_amount, $activeGroup->allowed_partial_percentage, 2);

          if (bccomp($value, $minAmount, 2) < 0) {
            $fail("The {$attribute} must be at least {$percentage}% of the required amount ({$total}).");
          }
        },
      ],
      'contribution_date' => [
        'required',
        'date',
        'before_or_equal:' . now($request->user()->timezone)->format('Y-m-d')
      ],
      'type' => [
        'sometimes',
        'in:regular,extra,makeup,penalty'
      ],
      'payment_method' => [
        'sometimes',
        'in:cash,bank_transfer,mobile_money'
      ],
      'transaction_reference' => [
        'nullable',
        'string'
      ]
    ]);

    // Find the current user's group membership
    $groupMember = GroupMember::where('user_id', Auth::id())
      ->where('group_id', $activeGroupId)
      ->firstOrFail();

    try {
      // Validate contribution state (ensure no issues with prior transactions)
      $this->contributionService
        ->validateContributionState(
          $groupMember,
          $activeGroup
        );

      // Determine contribution status and type
      $contributionStatus = $this->contributionService
        ->calculateContributionStatus(
          $activeGroup,
          $validatedData['amount']
        );

      // Create contribution
      $contribution = $this->contributionService
        ->storeContribution(
          $validatedData,
          $activeGroup,
          $groupMember,
          $contributionStatus
        );

      // Notify group admins about the new contribution
      $this->notifyGroupAdmins($contribution);

      return redirect()
        ->route('contributions.index')
        ->with('flush', $contributionStatus['message']);

    } catch (\Exception $e) {
      Log::error('Contribution creation failed', [
        'user_id' => Auth::id(),
        'group_id' => $activeGroupId,
        'error' => $e->getMessage()
      ]);

      return back()
        ->withErrors([
          'message' => $e->getMessage()
        ])
        ->withInput();
    }
  }

  public function show($uuid)
  {
    $contribution = Contribution::with([
      'user:id,name,uuid',
      'group',
      'groupMember'
    ])->where('uuid', $uuid)->firstOrFail();

    // Check user's role in the group
    $groupMember = GroupMember::where('user_id', Auth::id())
      ->where('group_id', $contribution->group_id)
      ->first();

    $isAdminOrTreasurer = $groupMember
      ? in_array($groupMember->role, ['admin', 'treasurer'])
      : false;

    // Ensure user can only view their own contribution or has admin/treasurer access
    if (!$isAdminOrTreasurer && $contribution->user_id !== Auth::id()) {
      abort(403, 'Unauthorized access');
    }

    // Prepare additional metadata
    $contribution->metadata = array_merge($contribution->metadata ?? [], [
      'activity_log' => $this->getContributionActivityLog($contribution)
    ]);

    return Inertia('Contributions/Show', [
      'contribution' => $contribution,
      'group' => $contribution->group,
      'currentUserRole' => $groupMember->role ?? 'member',
      'isAdminOrTreasurer' => $isAdminOrTreasurer
    ]);
  }

  protected function getContributionActivityLog(Contribution $contribution)
  {
    // Fetch group activities related to this contribution
    return GroupActivity::where('type', 'contribution_made')
      ->where('metadata->contribution_id', $contribution->id)
      ->orderByDesc('created_at')
      ->take(10)
      ->get()
      ->map(function ($activity) {
        return [
          'action' => $activity->description,
          'timestamp' => $activity->created_at,
          'type' => $activity->metadata['payment_type'] ?? 'info',
          'description' => json_encode($activity->changes)
        ];
      });
  }

// Verification Method
  public function verify(Request $request, $uuid)
  {
    $contribution = Contribution::where('uuid', $uuid)->firstOrFail();
    $group = $contribution->group;

    // Validate admin password
    if (!Hash::check($request->admin_password, Auth::user()->password)) {
      throw ValidationException::withMessages([
        'admin_password' => 'Invalid admin password'
      ]);
    }

    // If group requires group approval, validate treasurer password
    if ($group->require_group_approval) {
      $treasurer = GroupMember::where('group_id', $group->id)
        ->where('role', 'treasurer')
        ->first();

      if (!$treasurer || !Hash::check($request->treasurer_password, $treasurer->user->password)) {
        throw ValidationException::withMessages([
          'treasurer_password' => 'Invalid treasurer password'
        ]);
      }
    }

    // Begin transaction for atomic update
    DB::transaction(function () use ($contribution, $request) {
      // Update contribution status
      $contribution->update([
        'status' => $request->status,
        'is_verified' => true,
        'metadata' => array_merge($contribution->metadata ?? [], [
          'verification_notes' => $request->notes,
          'verified_by' => Auth::id(),
          'verification_timestamp' => now()
        ])
      ]);

      // Log verification activity
      GroupActivity::create([
        'group_id' => $contribution->group_id,
        'user_id' => Auth::id(),
        'type' => 'contribution_verified',
        'description' => 'Contribution verified',
        'changes' => [
          'previous_status' => $contribution->getOriginal('status'),
          'new_status' => $request->status
        ],
        'metadata' => [
          'contribution_id' => $contribution->id,
          'verified_by' => Auth::id()
        ]
      ]);
    });

    return back()->with('success', 'Contribution verified successfully');
  }

// Delete Method
  public function destroy(Request $request, $uuid)
  {
    $contribution = Contribution::where('uuid', $uuid)->firstOrFail();
    $group = $contribution->group;

    // Validate admin password
    if (!Hash::check($request->admin_password, Auth::user()->password)) {
      throw ValidationException::withMessages([
        'admin_password' => 'Invalid admin password'
      ]);
    }

    // If group requires group approval, validate treasurer password
    if ($group->require_group_approval) {
      $treasurer = GroupMember::where('group_id', $group->id)
        ->where('role', 'treasurer')
        ->first();

      if (!$treasurer || !Hash::check($request->treasurer_password, $treasurer->user->password)) {
        throw ValidationException::withMessages([
          'treasurer_password' => 'Invalid treasurer password'
        ]);
      }
    }

    // Begin transaction for atomic delete
    DB::transaction(function () use ($contribution) {
      // Log deletion activity before deleting
      GroupActivity::create([
        'group_id' => $contribution->group_id,
        'user_id' => Auth::id(),
        'type' => 'contribution_deleted',
        'description' => 'Contribution deleted',
        'changes' => [
          'contribution_details' => $contribution->toArray()
        ],
        'metadata' => [
          'contribution_id' => $contribution->id,
          'deleted_by' => Auth::id()
        ]
      ]);

      // Soft delete the contribution
      $contribution->delete();
    });

    return redirect()
      ->route('contributions.index')
      ->with('success', 'Contribution deleted successfully');
  }

  /**
   * Notify group admins about the new contribution
   */
  protected function notifyGroupAdmins(Contribution $contribution): void
  {
    // Find group admins and treasurers
    $admins = $contribution->group->members()
      ->whereIn('role', ['admin', 'treasurer'])
      ->get();

    // Send notifications
    Notification::send($admins, new NewContributionNotification($contribution));
  }
}
