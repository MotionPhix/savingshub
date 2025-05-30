<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
      \App\Http\Middleware\HandleInertiaRequests::class,
      \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
      \App\Http\Middleware\EnsureActiveGroup::class,
      \App\Http\Middleware\HandlePendingInvitation::class,
      \Inertia\EncryptHistoryMiddleware::class,
    ]);

    $middleware->alias([
      'active.group' => \App\Http\Middleware\EnsureActiveGroup::class,
      'group.currency' => \App\Http\Middleware\EnsureGroupCurrency::class,
      'pending.invitation' => \App\Http\Middleware\HandlePendingInvitation::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InvalidSignatureException $e) {

      // Log the invalid signature attempt
      Log::warning('Invalid invitation signature attempt', [
        'token' => $e->getMessage(),
      ]);

      // Render an Inertia error page
      return Inertia('Errors/LinkExpired', [
        'message' => 'The invitation link has expired or is invalid.',
        'status' => 403
      ]);

    });
  })->create();
