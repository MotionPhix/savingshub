<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Group;
use App\Models\GroupMember;

class GroupInvitationNotification extends Notification
{
  use Queueable;

  protected $group;
  protected $invitation;

  public function __construct(Group $group, GroupMember $invitation)
  {
    $this->group = $group;
    $this->invitation = $invitation;
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject("Invitation to Join {$this->group->name}")
      ->line("You have been invited to join the group: {$this->group->name}")
      ->action('Accept Invitation', route('groups.accept-invitation', $this->group));
  }

  public function toDatabase($notifiable)
  {
    return [
      'group_id' => $this->group->id,
      'group_name' => $this->group->name,
      'invitation_id' => $this->invitation->id,
      'type' => 'group_invitation',
      'message' => "Invited to join {$this->group->name}",
      'action_url' => route('groups.accept-invitation', $this->group)
    ];
  }
}
