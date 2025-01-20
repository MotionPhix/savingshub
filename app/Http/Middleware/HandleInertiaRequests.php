<?php

namespace App\Http\Middleware;

use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that is loaded on the first page visit.
   *
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determine the current asset version.
   */
  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  /**
   * Define the props that are shared by default.
   *
   * @return array<string, mixed>
   */
  public function share(Request $request): array
  {
    $cwg = session('active_group_id')
      ? \App\Models\Group::where('id', session('active_group_id'))
        ->first(['id', 'uuid', 'name', 'settings'])
      : null;

    // Find the current user's membership in the group
    $currentUserMembership = $cwg
      ? GroupMember::where('user_id', $request->user()->id)
        ->where('group_id', $cwg->id)
        ->first()
      : null;

    return [
      ...parent::share($request),
      'auth' => [
        'user' => fn() => ([
          ...$request->user()?->only(['name', 'gender', 'avatar', 'account_status', 'locale', 'timezone']),
          'groups_count' => $request->user()->withCount('groups')->first()->groups_count ?? 0,
        ]),
        'can' => [
          'create_group' => fn() => $request->user()?->canCreateGroup(),
          'invite_members' => fn() => Gate::allows('invite-members', $cwg),
          'edit_group' => fn() => Gate::allows('update', $cwg),
          'manage_admin' => fn() => $request->user()?->hasRole('super-admin'),
        ],
      ],

      'appName' => env('app_name'),

      'flush' => session('flush'),

      'current_group' => fn() => ([
        'id' => $cwg?->id,
        'uuid' => $cwg?->uuid,
        'name' => $cwg?->name,
        'currency' => $cwg?->settings['currency'],
        'current_role' => $currentUserMembership?->role,
        'contribution_amount' => $cwg->contribution_amount,
      ])
    ];
  }
}
