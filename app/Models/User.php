<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable /*implements MustVerifyEmail*/
{
  use HasFactory,
    Notifiable,
    HasRoles,
    BootUuid,
    SoftDeletes,
    HasApiTokens;

  protected $fillable = [
    'name',
    'email',
    'password',
    'gender',
    'user_type',
    'account_status',
    'max_groups',
    'subscription_ends_at',
    'phone_number',
    'avatar',
    'bio',
    'timezone',
    'locale'
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'subscription_ends_at' => 'datetime',
    ];
  }

  // Relationships
  /*public function groups(): HasMany
  {
    return $this->hasMany(Group::class, 'created_by');
  }*/

  /**
   * Relationship for user groups
   */
  public function groups()
  {
    return $this->belongsToMany(Group::class, 'group_members')
      ->withPivot('role', 'status')
      ->wherePivot('status', 'active');
  }

  public function groupMemberships(): HasMany
  {
    return $this->hasMany(GroupMember::class);
  }

  public function contributions(): HasManyThrough
  {
    return $this->hasManyThrough(Contribution::class, GroupMember::class);
  }

  public function loans(): HasManyThrough
  {
    return $this->hasManyThrough(Loan::class, GroupMember::class);
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('account_status', 'active');
  }

  // Checks and Permissions
  public function hasGroups(): bool
  {
    return $this->groupMemberships()->exists();
  }

  public function isFreeUser(): bool
  {
    return $this->user_type === 'free';
  }

  public function isPaidUser(): bool
  {
    return $this->user_type === 'paid';
  }

  public function isAccountActive(): bool
  {
    return $this->account_status === 'active';
  }

  public function canCreateGroup(): bool
  {
    if (!$this->isAccountActive()) {
      return false;
    }

    if ($this->isFreeUser()) {
      return $this->groups()->count() < $this->max_groups;
    }

    return true;
  }

  public function hasActiveSubscription(): bool
  {
    return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
  }

  // User Management Methods
  public function upgradeToPaidUser(int $maxGroups = 10)
  {
    $this->update([
      'user_type' => 'paid',
      'max_groups' => $maxGroups,
      'subscription_ends_at' => now()->addYear(),
      'account_status' => 'active'
    ]);
  }

  public function downgradeToFreeUser()
  {
    if ($this->groups()->count() > 1) {
      $this->groups()->latest()->limit($this->groups()->count() - 1)->delete();
    }

    $this->update([
      'user_type' => 'free',
      'max_groups' => 1,
      'subscription_ends_at' => null
    ]);
  }

  public function suspendAccount()
  {
    $this->update([
      'account_status' => 'suspended'
    ]);
  }

  /**
   * Get pending group invitations
   */
  public function pendingGroupInvitations()
  {
    return $this->belongsToMany(Group::class, 'group_members')
      ->withPivot('role', 'status')
      ->wherePivot('status', 'invited');
  }

  public function reactivateAccount()
  {
    $this->update([
      'account_status' => 'active'
    ]);
  }
}
