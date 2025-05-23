<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class GroupInvitationNotification extends Notification
{
  use Queueable;

  protected $group;
  protected $invitation;
  protected $customMessage;

  public function __construct(Group $group, GroupInvitation $invitation, ?string $customMessage = null)
  {
    $this->group = $group;
    $this->invitation = $invitation;
    $this->customMessage = $customMessage;
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {
    $invitationUrl = URL::temporarySignedRoute(
      'groups.invite.accept',
      now()->addDays(7),
      ['token' => $this->invitation->token]
    ); // Match invitation expiration

    $mailMessage = (new MailMessage)
      ->subject("You've been invited to join {$this->group->name}")
      ->greeting("Hello!")
      ->line("You have been invited to join the {$this->group->name} group.")
      ->line("Your role will be: " . ucfirst($this->invitation->role));

    // Add custom message if provided
    if ($this->customMessage) {
      $mailMessage->line("{$this->customMessage}");
    }

    $mailMessage->action(
      'Accept Invitation',
      $invitationUrl // Use the signed URL directly
    )->line('This invitation will expire in 7 days.')
      ->line('If you did not expect this invitation, you can ignore this email.');

    return $mailMessage;
  }
}
