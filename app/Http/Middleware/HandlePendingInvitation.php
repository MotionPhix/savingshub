<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HandlePendingInvitation
{
  public function handle(Request $request, Closure $next)
  {
    // Check if there's a pending invitation token in the session
    $invitationToken = Session::get('pending_invitation_token');

    if ($invitationToken && $request->user()) {
      // Clear the session token to prevent repeated redirects
      Session::forget('pending_invitation_token');

      // Redirect to invitation acceptance
      return redirect()->route('groups.invite.accept', ['token' => $invitationToken]);
    }

    return $next($request);
  }
}
