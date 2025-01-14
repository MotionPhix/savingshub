<?php

namespace App\Models;

use App\Notifications\GroupInvitationNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class GroupInvitation extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'group_id',
    'email',
    'role',
    'token',
    'expires_at',
    'invited_by',
    'accepted_at'
  ];

  protected $dates = [
    'expires_at',
    'accepted_at'
  ];

  public function group()
  {
    return $this->belongsTo(Group::class);
  }

  public function invitedBy()
  {
    return $this->belongsTo(User::class, 'invited_by');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->whereNull('accepted_at')
      ->where('expires_at', '>', now());
  }

  // Check if invitation is still valid
  public function isValid(): bool
  {
    return !$this->accepted_at && now()->lessThan($this->expires_at);
  }

  // Generate expiration date
  public function generateExpirationDate()
  {
    return now()->addDays(7);
  }

  // Resend invitation
  public function resend()
  {
    // Reset token and expiration
    $this->token = Str::random(60);
    $this->expires_at = $this->generateExpirationDate();
    $this->save();

    // Resend notification
    Notification::route('mail', $this->email)
      ->notify(new GroupInvitationNotification(
        $this->group,
        $this
      ));
  }
}
