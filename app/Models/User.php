<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasMedia /*implements MustVerifyEmail*/
{
  use HasFactory,
    Notifiable,
    HasRoles,
    BootUuid,
    SoftDeletes,
    HasApiTokens,
    InteractsWithMedia;

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
    'bio',
    'timezone',
    'locale'
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected $appends = [
    'avatar'
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'subscription_ends_at' => 'datetime',
    ];
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('avatar')
      ->singleFile() // Ensures only one avatar is kept
      ->useFallbackUrl(url($this->defaultAvatar($this->gender)))
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('thumb')
          ->width(150)
          ->height(150)
          ->sharpen(10);

        $this->addMediaConversion('medium')
          ->width(300)
          ->height(300);
      });
  }

  // Convenience method to get avatar URL
  public function avatar(): Attribute
  {
    return Attribute::get(
      fn() => $this->getFirstMediaUrl('avatar', 'thumb')
        ?: $this->getFirstMediaUrl('avatar')
        ?: url($this->defaultAvatar($this->gendeer))
    );
  }

  private function defaultAvatar(string $gender = null)
  {
    return $gender
      ? $gender === 'male' ? '/default-m-avatar.png' : '/default-f-avatar.png'
      : '/default-m-avatar.png';
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
