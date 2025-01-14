<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ErrorHandlingService
{
  public function reportError(
    Throwable $exception,
    ?string $context = null,
    ?array $additionalData = null
  ) {
    $errorData = [
      'message' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'trace' => $exception->getTraceAsString(),
      'context' => $context,
      'user_id' => auth()->id(),
      'ip_address' => request()->ip(),
      'url' => request()->fullUrl(),
      'method' => request()->method(),
    ];

    // Add any additional data
    if ($additionalData) {
      $errorData = array_merge($errorData, $additionalData);
    }

    // Log to different channels based on severity
    if ($this->iscritical($exception)) {
      Log::channel('critical_errors')->critical(
        "Critical Error: {$exception->getMessage()}",
        $errorData
      );
    } else {
      Log::error(
        "Error in {$context}: {$exception->getMessage()}",
        $errorData
      );
    }

    // Optionally send notification to admin
    $this->notifyAdminIfCritical($exception, $errorData);
  }

  private function isCritical(Throwable $exception): bool
  {
    $criticalExceptions = [
      \Illuminate\Database\QueryException::class,
      \Symfony\Component\HttpKernel\Exception\HttpException::class,
      \Illuminate\Auth\AuthenticationException::class,
    ];

    foreach ($criticalExceptions as $criticalException) {
      if ($exception instanceof $criticalException) {
        return true;
      }
    }

    return false;
  }

  private function notifyAdminIfCritical(Throwable $exception, array $errorData)
  {
    if ($this->isCritical($exception)) {
      // Send email or Slack notification to admin
      Notification::route('mail', config('app.admin_email'))
        ->notify(new \App\Notifications\CriticalErrorNotification(
          $errorData
        ));
    }
  }
}
