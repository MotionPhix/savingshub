<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GroupInvitation;

class SendInvitationExpirationWarnings extends Command
{
  protected $signature = 'invitations:check-expiration';
  protected $description = 'Send warnings for expiring group invitations';

  public function handle()
  {
    $expiringInvitations = GroupInvitation::where('accepted_at', null)
      ->where('expires_at', '<=', now()->addDays(2))
      ->get();

    foreach ($expiringInvitations as $invitation) {
      try {
        $invitation->sendExpirationWarning();

        // Log the warning
        activity('group_invitations')
          ->performedOn($invitation->group)
          ->withProperties([
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'days_remaining' => now()->diffInDays($invitation->expires_at)
          ])
          ->log('invitation_expiration_warning_sent');

      } catch (\Exception $e) {
        // Log any errors in sending warnings
        \Log::error('Invitation expiration warning failed', [
          'invitation_id' => $invitation->id,
          'error' => $e->getMessage()
        ]);
      }
    }

    $this->info('Invitation expiration warnings processed.');
  }
}
