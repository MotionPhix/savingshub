<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupInvitationController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guest Routes (No authentication required)
Route::middleware('guest')->group(function () {
  // Authentication and Registration Routes
  Route::get('register', [RegisteredUserController::class, 'create'])
    ->name('register');

  Route::post(
    '/register/with-invitation',
    [GroupInvitationController::class, 'registerWithInvitation']
  )->name('register.with_invitation');

  Route::post('register', [RegisteredUserController::class, 'store']);

  Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

  Route::post('login', [AuthenticatedSessionController::class, 'store']);

  // Password Reset Routes
  Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('password.request');

  Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email');

  Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

  Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');
});

// Authenticated Routes
Route::middleware(['auth', 'verified', 'group.currency'])->group(function () {
  // Email Verification Routes
  Route::get('verify-email', EmailVerificationPromptController::class)
    ->name('verification.notice');

  Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

  Route::post(
    'email/verification-notification',
    [EmailVerificationNotificationController::class, 'store']
  )->middleware('throttle:6,1')
    ->name('verification.send');

  // Password Management Routes
  Route::get(
    'confirm-password',
    [ConfirmablePasswordController::class, 'show']
  )->name('password.confirm');

  Route::post(
    'confirm-password',
    [ConfirmablePasswordController::class, 'store']
  );

  Route::put(
    'password',
    [PasswordController::class, 'update']
  )->name('password.update');

  Route::delete(
    'logout',
    [AuthenticatedSessionController::class, 'destroy']
  )->name('logout');

  // Unrestricted Routes (Dashboard and Profile)

  // Auth user profile management
  Route::prefix('profile')->group(function () {

    Route::get(
      '/s',
      [ProfileController::class, 'edit']
    )->name('profile.index');

    Route::patch(
      '/u/{user:uuid}',
      [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
      '/a/d',
      [ProfileController::class, 'deleteAvatar']
    )->name('profile.avatar.destroy');

    Route::delete(
      '/p/d',
      [ProfileController::class, 'destroy']
    )->name('profile.destroy');

  });

  // Group Routes (Requires active group middleware)
  Route::middleware('active.group')->group(function () {

    Route::get(
      '/',
      [DashboardController::class, 'index']
    )->name('dashboard');

    // groups
    Route::prefix('groups')->name('groups.')->group(function () {

      Route::get(
        '/s/{group:uuid}',
        [GroupController::class, 'show']
      )->name('show');

      // Group Settings
      Route::get(
        '/c/settings',
        function () {
          $currencyService = app(\App\Services\CurrencyService::class);

          return Inertia::modal('Groups/CurrencySetter', [
            'availableCurrency' => $currencyService->getAvailableCurrencies()
          ])->baseUrl('/groups');
        }
      )->name('settings.currency');

      Route::patch(
        '/u/settings/{group:uuid}',
        [GroupController::class, 'updateSettings']
      )->name('settings');

      Route::get(
        '/i/form/{group:uuid}',
        [GroupController::class, 'showInvite']
      )->name('invite.form');

      Route::post(
        '/i/members',
        [GroupController::class, 'invite']
      )->name('invite.send');

      Route::post(
        '/i/resend/{invitation:uuid}',
        [GroupInvitationController::class, 'resendInvitation']
      )->name('invite.resend')
        ->middleware('can:resend,invitation');
    });

    // Contributions Routes
    Route::prefix('contributions')
      ->name('contributions.')
      ->group(function () {
        Route::get(
          '/',
          [ContributionController::class, 'index']
        )->name('index');

        Route::get(
          '/new-contribution',
          [ContributionController::class, 'create']
        )->name('create');

        Route::post(
          '/',
          [ContributionController::class, 'store']
        )->name('store');

        Route::get(
          '/s/{contribution:uuid}',
          [ContributionController::class, 'show']
        )->name('show');

        Route::put(
          '/u/{contribution:uuid}',
          [ContributionController::class, 'update']
        )->name('update');

        Route::delete(
          '/d/{contribution:uuid}',
          [ContributionController::class, 'destroy']
        )->name('destroy');
      });

    // Loans Routes
    Route::prefix('loans')
      ->name('loans.')
      ->group(function () {
        Route::get(
          '/',
          [LoanController::class, 'index']
        )->name('index');

        Route::get(
          '/new-loan',
          [LoanController::class, 'create']
        )->name('create');

        Route::post(
          '/',
          [LoanController::class, 'store']
        )->name('store');

        Route::get(
          '/s/{loan:uuid}',
          [LoanController::class, 'show']
        )->name('show');

        Route::put(
          '/u/{loan:uuid}',
          [LoanController::class, 'update']
        )->name('update');

        Route::delete(
          '/d/{loan:uuid}',
          [LoanController::class, 'destroy']
        )->name('destroy');

      });
  });

  // Group Management Routes (Unrestricted)
  Route::prefix('groups')
    ->name('groups.')
    ->group(function () {

      Route::get(
        '/',
        [GroupController::class, 'index']
      )->name('index');

      Route::get(
        '/new-group',
        [GroupController::class, 'create']
      )->name('create');

      Route::get(
        '/e/{group:uuid}',
        [GroupController::class, 'edit']
      )->name('edit');

      Route::post(
        '/',
        [GroupController::class, 'store']
      )->name('store');

      Route::post(
        '/s/{group:uuid}',
        [GroupController::class, 'activate']
      )->name('set.active');

      // Group Invitation Routes
      Route::get(
        '/i/a/{token}',
        [GroupInvitationController::class, 'handleInvitationLink']
      )->name('invite.accept')->middleware('signed');

      Route::get(
        '/i/d/{token}',
        [GroupInvitationController::class, 'decline']
      )->name('invite.decline')->middleware('signed');
    });

  // Members Routes
  Route::prefix('members')->name('members.')->group(function () {
    Route::get(
      '/',
      [GroupMemberController::class, 'index']
    )->name('index');

    Route::post(
      '/change-role/{user:uuid}',
      [GroupMemberController::class, 'changeRole']
    )->name('change-role');

    Route::delete(
      '/d/{user:uuid}',
      [GroupMemberController::class, 'remove']
    )->name('remove');
  });
});
