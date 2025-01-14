<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\GroupInvitation;

class InvitationExpirationWarning extends Notification
{
  protected $invitation;

  public function __construct(GroupInvitation $invitation)
  {
    $this->invitation = $invitation;
  }

  public function toMail($notifiable)
  {
    $daysRemaining = now()->diffInDays($this->invitation->expires_at);
    $acceptUrl = route('group.accept_invitation', [
      'token' => $this->invitation->token
    ]);

    return (new MailMessage)
      ->subject("Invitation Expiring Soon - {$this->invitation->group->name}")
      ->line("Your invitation to join {$this->invitation->group->name} is expiring soon.")
      ->line("You have {$daysRemaining} days left to accept this invitation.")
      ->action('Accept Invitation', $acceptUrl)
      ->line('Don\'t miss out on joining this group!');
  }
}
