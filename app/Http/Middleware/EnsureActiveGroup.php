<?php

/*namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SetActiveGroup
{
  public function handle(Request $request, Closure $next)
  {
    // Check if user is authenticated
    if (Auth::check()) {
      // Get the active group from session or default to first group
      $activeGroupId = session('active_group_id');

      if ($activeGroupId) {
        // Validate that the user is actually a member of this group
        $group = Auth::user()->groups()->find($activeGroupId);

        if ($group) {
          // Share the active group with all views
          view()->share('activeGroup', $group);
          $request->merge(['active_group' => $group]);
        } else {
          // Reset active group if not a member
          session()->forget('active_group_id');
        }
      }
    }

    return $next($request);
  }
}*/


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EnsureActiveGroup
{
  protected $excludedRoutes = [
    'dashboard',
    'groups.index',
    'groups.create',
    'profile.edit',
    'profile.update'
  ];

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
        $userGroups = $request->user()->groups;

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

    return Inertia::render('Groups/NoGroups', [
      'can_create_group' => true,
      'message' => 'You do not have any groups. Create your first group to get started!'
    ]);
  }

  protected function redirectToGroupSelection(Request $request, $userGroups)
  {
    // Inertia or redirect based on request type
    if ($request->wantsJson()) {
      return response()->json([
        'redirect' => route('groups.index'),
        'message' => 'Please select an active group.',
        'available_groups' => $userGroups
      ], 409);
    }

    return Inertia::render('Groups/SelectGroup', [
      'groups' => $userGroups,
      'message' => 'Please select a group to continue.'
    ]);
  }
}
