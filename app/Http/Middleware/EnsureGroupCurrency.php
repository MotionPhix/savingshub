<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupCurrency
{
  public function handle(Request $request, Closure $next)
  {
    // Check if there's an active group in the session
    $activeGroupId = session('active_group_id');

    if (!$activeGroupId) {
      // No active group, proceed normally
      return $next($request);
    }

    // Retrieve the active group
    $group = \App\Models\Group::findOrFail($activeGroupId);
    $currencyService = app(\App\Services\CurrencyService::class);

    // Check if group needs currency configuration
    /*if ($currencyService->groupNeedsCurrencyConfiguration($group) &&
      !$request->url() !== 'groups/settings/currency') {
      // Redirect to settings with a flash message
      return Inertia::modal('Groups/CurrencySetter', [
        'warning' => 'Please configure your group\'s currency settings.',
        'availableCurrencies' => $currencyService->getAvailableCurrencies()
      ])->baseUrl('/groups');
    }*/

    return $next($request);
  }
}
