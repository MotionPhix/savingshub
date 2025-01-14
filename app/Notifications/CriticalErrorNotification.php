<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CriticalErrorNotification extends Notification
{
  protected $errorData;

  public function __construct(array $errorData)
  {
    $this->errorData = $errorData;
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->error()
      ->subject('Critical Error in ' . config('app.name'))
      ->line('A critical error has occurred in the application.')
      ->line('Error Message: ' . $this->errorData['message'])
      ->line('Location: ' . $this->errorData['file'] . ':' . $this->errorData['line'])
      ->line('User ID: ' . ($this->errorData['user_id'] ?? 'N/A'))
      ->line('URL: ' . $this->errorData['url'])
      ->line('Method: ' . $this->errorData['method']);
  }
}
