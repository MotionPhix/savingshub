<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller implements HasMiddleware
{
  public function __construct(protected GroupService $groupService) {}

  /**
   * Get the middleware that should be assigned to the controller.
   */
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
    /*$groups = Group::with(['creator', 'members'])
      ->when(
        !auth()->user()->hasRole('super-admin'),
        fn($query) => $query->whereHas('members',
          fn($q) => $q->where('user_id', auth()->id())
        )
      )
      ->latest()
      ->paginate(10);

    return Inertia('Groups/Index', [
      'groups' => $groups,
      'stats' => [
        'total_groups' => $groups->total(),
        'active_groups' => Group::activeGroups()->count()
      ]
    ]);*/

    $user = auth()->user();

    // For free users, show their own groups
    // For paid users, potentially show more groups
    $groups = $user->isFreeUser()
      ? $user->groups
      : Group::whereHas('members', function($query) use ($user) {
        $query->where('user_id', $user->id);
      })->get();

    return Inertia('Groups/Index', [
      'groups' => $groups
    ]);
  }

  public function create()
  {
    $user = Auth::user();

    if (! $user->canCreateGroup()) {
      return redirect()->back();
    }

    return Inertia('Groups/GroupForm', [
      'group_types' => $this->getGroupTypes(),
      'contribution_frequencies' => $this->getContributionFrequencies(),
      'loan_interest_types' => $this->getLoanInterestTypes()
    ]);
  }

  public function store(Request $request)
  {
    // Validate group creation
    $validatedData = $request->validate([
      'name' => 'required|string|max:255|unique:groups,name',
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
        auth()->user(),
        $validatedData
      );

      return redirect()->route('groups.show', $group)
        ->with('success', 'Group created successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  public function show(Group $group)
  {
    // Authorize group view
    Gate::authorize('view', $group);

    // Load detailed group information
    $group->load([
      'members.user',
      'contributions',
      'loans'
    ]);

    return Inertia('Groups/Show', [
      'group' => $group,
      'canManageGroup' => Gate::allows('update', $group)
    ]);
  }

  public function edit(Group $group)
  {
    return Inertia('Groups/Edit', [
      'group' => $group
    ]);
  }

  public function update(Request $request, Group $group)
  {
    $validatedData = $request->validate([
      'name' => 'sometimes|string|max:255|unique:groups,name,' . $group->id,
      'description' => 'nullable|string|max:1000',
      'contribution_frequency' => 'sometimes|in:weekly,monthly,quarterly,annually',
      'contribution_amount' => 'sometimes|numeric|min:1',
      'loan_interest_type' => 'sometimes|in:fixed,variable,tiered',
      'base_interest_rate' => 'sometimes|numeric|min:0|max:100',
    ]);

    try {
      $updatedGroup = $this->groupService->updateGroup($group, $validatedData);

      return redirect()->route('groups.show', $updatedGroup)
        ->with('success', 'Group updated successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Invite members to the group
   */
  public function inviteMembers(Request $request, Group $group)
  {
    Gate::authorize('inviteMembers', $group);

    $validatedData = $request->validate([
      'emails' => 'required|array',
      'emails.*' => 'email|exists:users,email'
    ]);

    try {
      $invitations = $this->groupService->inviteMembers(
        $group,
        $validatedData['emails']
      );

      return back()->with('success', 'Invitations sent successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Accept group invitation
   */
  public function acceptInvitation(Group $group)
  {
    try {
      $membership = $this->groupService->acceptGroupInvitation(
        auth()->user(),
        $group
      );

      return redirect()->route('groups.show', $group)
        ->with('success', 'You have joined the group');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Remove a member from the group
   */
  public function removeMember(Group $group, User $user)
  {
    Gate::authorize('removeMember', [$group, $user]);

    try {
      $this->groupService->removeMember($group, $user);

      return back()->with('success', 'Member removed successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Change member role
   */
  public function changeMemberRole(Request $request, Group $group, User $user)
  {
    Gate::authorize('changeMemberRole', [$group, $user]);

    $validatedData = $request->validate([
      'role' => 'required|in:admin,member,treasurer,secretary'
    ]);

    try {
      $this->groupService->changeMemberRole(
        $group,
        $user,
        $validatedData['role']
      );

      return back()->with('success', 'Member role updated');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Leave the group
   */
  public function leaveGroup(Group $group)
  {
    try {
      $this->groupService->leaveGroup(
        auth()->user(),
        $group
      );

      return redirect()->route('groups.index')
        ->with('success', 'You have left the group');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Dissolve the group
   */
  public function dissolveGroup(Group $group)
  {
    Gate::authorize('dissolve', $group);

    try {
      $this->groupService->dissolveGroup($group);

      return redirect()->route('groups.index')
        ->with('success', 'Group dissolved successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  public function destroy(Group $group)
  {
    $group->delete();
    return redirect()->route('groups.index')->with('success', 'Group deleted successfully.');
  }

  // Helper methods for dropdown options
  protected function getGroupTypes(): array
  {
    return [
      'savings' => 'Savings Group',
      'investment' => 'Investment Club',
      'loan' => 'Loan Group',
      'social' => 'Social Welfare Group'
    ];
  }

  protected function getContributionFrequencies(): array
  {
    return [
      'weekly' => 'Weekly',
      'monthly' => 'Monthly',
      'quarterly' => 'Quarterly',
      'annually' => 'Annually'
    ];
  }

  protected function getLoanInterestTypes(): array
  {
    return [
      'fixed' => 'Fixed Rate',
      'variable' => 'Variable Rate',
      'tiered' => 'Tiered Rate'
    ];
  }
}
