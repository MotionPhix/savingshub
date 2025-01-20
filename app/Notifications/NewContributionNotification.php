<?php

namespace App\Notifications;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class NewContributionNotification extends Notification
{
  private $contribution;

  public function __construct(Contribution $contribution)
  {
    $this->contribution = $contribution;
  }

  public function toArray($notifiable)
  {
    return [
      'contribution_id' => $this->contribution->id,
      'user_name' => $this->contribution->user->name,
      'amount' => $this->contribution->amount,
      'status' => $this->contribution->status,
      'group_name' => $this->contribution->group->name,
    ];
  }

  public function toDatabase($notifiable)
  {
    return $this->toArray($notifiable);
  }

  public function toMail($notifiable)
  {
    $currency = session('active_group_id')
      ? \App\Models\Group::where('id', session('active_group_id'))
      ->first()->settings['currency'] ?? 'MWK'
      : 'ZAR';

    return (new MailMessage)
      ->subject("New Contribution Submitted in {$this->contribution->group->name}")
      ->line("A new contribution has been submitted.")
      ->line("Amount: " . Number::currency($this->contribution->amount, in: $currency))
      ->line("Status: {$this->contribution->status}")
      ->action('Review Contribution', route('contributions.show', $this->contribution));
  }
}
