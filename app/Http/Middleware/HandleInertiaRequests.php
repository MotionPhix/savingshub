<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
    return [
      ...parent::share($request),
      'auth' => [
        'user' => fn() => $request->user()?->only(['name', 'gender', 'avatar', 'account_status', 'locale', 'timezone']),
        'can' => [
          'create_group' => fn() => $request->user()?->canCreateGroup(),
        ],
      ],

      'appName' => env('app_name'),
      'currency' => fn() => session('active_group_id')
        ? \App\Models\Group::where('id', session('active_group_id'))
          ->first()->settings['currency'] ?? 'MWK'
        : 'ZAR',

      'flush' => session('flush'),

    ];
  }
}
