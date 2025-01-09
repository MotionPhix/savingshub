<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDefaultNotification extends Notification
{
  use Queueable;

  protected $loan;
  protected $penalty;

  public function __construct(Loan $loan, float $penalty)
  {
    $this->loan = $loan;
    $this->penalty = $penalty;
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->error()
      ->subject('Loan Defaulted')
      ->line('Your loan has been marked as defaulted.')
      ->line("Loan Amount: {$this->loan->total_amount}")
      ->line("Penalty Amount: {$this->penalty}")
      ->action('View Loan Details', route('groups.loans.show', [
        $this->loan->group,
        $this->loan
      ]));
  }

  public function toDatabase($notifiable)
  {
    return [
      'loan_id' => $this->loan->id,
      'group_id' => $this->loan->group_id,
      'type' => 'loan_default',
      'message' => "Loan defaulted. Penalty: {$this->penalty}",
      'action_url' => route('groups.loans.show', [
        $this->loan->group,
        $this->loan
      ])
    ];
  }
}
