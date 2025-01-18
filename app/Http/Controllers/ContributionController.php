<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributionController extends Controller
{
  public function index(Request $request)
  {
    // Get the active group ID from session
    $activeGroupId = session('active_group_id');

    // Fetch the active group
    $activeGroup = Group::findOrFail($activeGroupId);

    // Fetch contributions with filters
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

      'pending_total' => $contributions->where('status', 'pending')->sum('amount'),
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
    ];

    return Inertia('Contributions/Index', [
      'contributions' => $contributions,
      'contributionInsights' => $contributionInsights,
      'activeGroup' => $activeGroup
    ]);

  }

  public function create()
  {
    // Get the active group ID from session
    $activeGroupId = session('active_group_id');

    // Fetch the active group
    $activeGroup = Group::findOrFail($activeGroupId);

    // Check user's roles in the group
    $groupMember = GroupMember::where('user_id', Auth::id())
      ->where('group_id', $activeGroupId)
      ->first();

    // Determine if user has permission to add contributions
    $canAddContributions = $groupMember && (($groupMember->role === 'treasurer' || $groupMember->role === 'admin'));

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

    // Contribution configuration from group settings
    $contributionConfig = [
      'minimum_amount' => $activeGroup->minimum_contribution,
      'maximum_amount' => $activeGroup->maximum_contribution,
      'default_amount' => $activeGroup->contribution_amount,
      'contribution_frequency' => $activeGroup->contribution_frequency
    ];

    // Additional context for contribution creation
    $context = [
      'activeGroup' => $activeGroup,
      'groupMembers' => $groupMembers,
      'contributionConfig' => $contributionConfig,
      'userRoles' => $groupMember ? $groupMember->roles->pluck('name') : [],
      'canAddContributions' => $canAddContributions
    ];

    return Inertia('Contributions/Create', $context);
  }

  public function store(Request $request)
  {
    $request->validate([
      'amount' => 'required|numeric',
      'contribution_date' => 'required|date',
    ]);

    Contribution::create([
      'group_member_id' => Auth::user()->groupMember->id,
      'amount' => $request->amount,
      'contribution_date' => $request->contribution_date,
    ]);

    return redirect()->route('contributions.index')->with('success', 'Contribution added successfully.');
  }
}
