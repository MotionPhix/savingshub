<?php

namespace App\Http\Middleware;

use App\Services\GroupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EnsureActiveGroup
{
  protected $excludedRoutes = [
    'groups.index',
    'groups.store',
    'groups.set.active',
    'groups.store',
    'groups.invite.accept',
    'groups.invite.decline',
    'groups.invite.send',
    'profile.index',
    'profile.avatar.destroy',
    'profile.update',
    'logout'
  ];

  public function __construct(
    protected GroupService $groupService
  ) {}

  public function handle(Request $request, Closure $next)
  {
    // Check if user is authenticated
    if (Auth::check()) {
      // Skip check for excluded routes
      if ($this->shouldSkipCheck($request)) {
        return $next($request);
      }

      // Check for active group
      $activeGroupId = session('active_group_id');

      if (!$activeGroupId) {
        // Get user's groups
        $userGroups = $request->user()
          ->groups()
          ->withCount('members')
          ->withSum('contributions', 'amount')
          ->get()
          ->map(function ($group) {
            return [
              'id' => $group->id,
              'uuid' => $group->uuid,
              'name' => $group->name,
              'mission_statement' => $group->mission_statement,
              'slug' => $group->slug,
              'members_count' => $group->members_count,
              'contributions_sum' => $group->contributions_sum,
            ];
          });;

        // No groups exist
        if ($userGroups->isEmpty()) {
          return $this->redirectToGroupCreation($request);
        }

        // Prompt to select a group
        return $this->redirectToGroupSelection($request, $userGroups);
      }
    }

    return $next($request);
  }

  protected function shouldSkipCheck(Request $request)
  {
    $routeName = $request->route()->getName();
    return in_array($routeName, $this->excludedRoutes);
  }

  protected function redirectToGroupCreation(Request $request)
  {
    // Inertia or redirect based on request type
    if ($request->wantsJson()) {
      return response()->json([
        'redirect' => route('groups.create'),
        'message' => 'You need to create a group first.'
      ], 409);
    }

    return Inertia::render('Groups/Create', [
      'can_create_group' => true,
      'message' => 'You do not have any groups. Create your first group to get started!'
    ]);
  }

  protected function redirectToGroupSelection(Request $request, $userGroups)
  {
    if (count($userGroups) === 1) {

      $groupToActivate = \App\Models\Group::where('id', $userGroups[0]['id'])->first();

      $this->groupService->activateGroup($request, $groupToActivate);

      return redirect(route('groups.show', $groupToActivate->uuid));
    }

    // Inertia or redirect based on request type
    if ($request->wantsJson()) {
      return response()->json([
        'redirect' => route('groups.index'),
        'message' => 'Please select an active group.',
        'available_groups' => $userGroups
      ], 409);
    }

    return Inertia::modal('Groups/SelectGroup', [
      'groups' => $userGroups,
      'message' => 'Please select a group to continue.'
    ])->baseUrl('/groups');
  }
}
