<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

  // Check if invitation is still valid
  public function isValid()
  {
    return !$this->accepted_at && now()->lessThan($this->expires_at);
  }
}
