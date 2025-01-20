<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class GroupMember extends Model
{
  use HasFactory, BootUuid, SoftDeletes;

  protected $fillable = [
    'group_id',
    'user_id',
    'role',
    'status',
    'total_contributions',
    'total_loans',
    'contribution_count',
    'loan_count',
    'custom_permissions',
    'joined_at',
    'last_activity_at'
  ];

  protected $casts = [
    'total_contributions' => 'decimal:2',
    'total_loans' => 'decimal:2',
    'custom_permissions' => 'array',
    'joined_at' => 'datetime',
    'last_activity_at' => 'datetime'
  ];

  protected $hidden = [
    'custom_permissions'
  ];

  // Relationships
  public function group(): BelongsTo
  {
    return $this->belongsTo(Group::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function contributions(): HasMany
  {
    return $this->hasMany(Contribution::class);
  }

  public function loans(): HasMany
  {
    return $this->hasMany(Loan::class);
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeAdmins($query)
  {
    return $query->where('role', 'admin');
  }

  // Status and Role Checks
  public function isAdmin(): bool
  {
    return $this->role === 'admin';
  }

  public function isMember(): bool
  {
    return $this->role === 'member';
  }

  public function isActive(): bool
  {
    return $this->status === 'active';
  }

  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  // Contribution and Loan Tracking
  public function updateContributionStats(Contribution $contribution)
  {
    $this->increment('contribution_count');
    $this->increment('total_contributions', $contribution->amount);
    $this->updateLastActivity();
  }

  public function updateLoanStats(Loan $loan)
  {
    $this->increment('loan_count');
    $this->increment('total_loans', $loan->amount);
    $this->updateLastActivity();
  }

  // Permissions Management
  public function hasCustomPermission(string $permission): bool
  {
    return in_array($permission, $this->custom_permissions ?? []);
  }

  public function addCustomPermission(string $permission)
  {
    $permissions = $this->custom_permissions ?? [];

    if (!in_array($permission, $permissions)) {
      $permissions[] = $permission;
      $this->custom_permissions = $permissions;
      $this->save();
    }
  }

  public function removeCustomPermission(string $permission)
  {
    $permissions = $this->custom_permissions ?? [];

    $this->custom_permissions = array_diff($permissions, [$permission]);
    $this->save();
  }

  // Activity Tracking
  public function updateLastActivity()
  {
    $this->last_activity_at = now();
    $this->save();
  }

  // Membership Lifecycle
  public function activate()
  {
    $this->update([
      'status' => 'active',
      'joined_at' => now()
    ]);
  }

  public function suspend()
  {
    $this->update([
      'status' => 'suspended'
    ]);
  }

  public function leave()
  {
    $this->update([
      'status' => 'left',
      'role' => 'member'
    ]);
  }

  // Computed Attributes
  public function getContributionRatioAttribute(): float
  {
    $groupTotalContributions = $this->group->contributions()->sum('amount');
    return $groupTotalContributions > 0
      ? $this->total_contributions / $groupTotalContributions
      : 0;
  }

  public function getLoanRatioAttribute(): float
  {
    $groupTotalLoans = $this->group->loans()->sum('amount');
    return $groupTotalLoans > 0
      ? $this->total_loans / $groupTotalLoans
      : 0;
  }
}
