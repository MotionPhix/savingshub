<?php

namespace App\Http\Middleware;

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
}
