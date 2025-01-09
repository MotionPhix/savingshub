<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Loan;

class LoanRequestNotification extends Notification
{
  use Queueable;

  protected $loan;

  public function __construct(Loan $loan)
  {
    $this->loan = $loan;
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject('New Loan Request')
      ->line('A new loan request has been submitted.')
      ->line("Amount: {$this->loan->principal_amount}")
      ->action('Review Loan', route('groups.loans.show', [
        $this->loan->group,
        $this->loan
      ]));
  }

  public function toDatabase($notifiable)
  {
    return [
      'loan_id' => $this->loan->id,
      'group_id' => $this->loan->group_id,
      'type' => 'loan_request',
      'message' => "New loan request of {$this->loan->principal_amount} submitted",
      'action_url' => route('groups.loans.show', [
        $this->loan->group,
        $this->loan
      ])
    ];
  }
}
