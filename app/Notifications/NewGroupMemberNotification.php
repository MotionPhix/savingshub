<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewGroupMemberNotification extends Notification
{
  use Queueable;

  protected $group;
  protected $newMember;
  protected $role;

  public function __construct(Group $group, User $newMember, string $role)
  {
    $this->group = $group;
    $this->newMember = $newMember;
    $this->role = $role;
  }

  public function via($notifiable)
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject("New Member Joined {$this->group->name}")
      ->line("{$this->newMember->name} has joined your group {$this->group->name}")
      ->line("Role: " . ucfirst($this->role))
      ->action('View Group', route('groups.show', $this->group));
  }

  public function toDatabase($notifiable)
  {
    return [
      'group_id' => $this->group->id,
      'group_name' => $this->group->name,
      'new_member_id' => $this->newMember->id,
      'new_member_name' => $this->newMember->name,
      'role' => $this->role,
      'type' => 'new_group_member'
    ];
  }
}
