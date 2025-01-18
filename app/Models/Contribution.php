<?php

namespace App\Models;

use App\Traits\BootUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Contribution extends Model
{
  use HasFactory, BootUuid, SoftDeletes;

  protected $fillable = [
    'group_member_id',
    'group_id',
    'user_id',
    'amount',
    'contribution_date',
    'status',
    'type',
    'payment_method',
    'transaction_reference',
    'is_verified',
    'metadata'
  ];

  protected $casts = [
    'amount' => 'decimal:2',
    'contribution_date' => 'date',
    'is_verified' => 'boolean',
    'metadata' => 'array'
  ];

  protected $hidden = [
    'metadata'
  ];

  // Relationships
  public function groupMember(): BelongsTo
  {
    return $this->belongsTo(GroupMember::class);
  }

  public function group(): BelongsTo
  {
    return $this->belongsTo(Group::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  // Scopes
  public function scopePaid($query)
  {
    return $query->where('status', 'paid');
  }

  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  public function scopeVerified($query)
  {
    return $query->where('is_verified', true);
  }

  // Status Checks
  public function isPaid(): bool
  {
    return $this->status === 'paid';
  }

  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  public function isOverdue(): bool
  {
    return $this->status === 'overdue' ||
      ($this->status === 'pending' && now()->isAfter($this->contribution_date));
  }

  // Contribution Lifecycle Management
  public function markAsPaid(string $paymentMethod = null, string $transactionReference = null)
  {
    return DB::transaction(function () use ($paymentMethod, $transactionReference) {
      $this->update([
        'status' => 'paid',
        'payment_method' => $paymentMethod,
        'transaction_reference' => $transactionReference,
        'is_verified' => true
      ]);

      // Update group member contribution stats
      $this->groupMember->updateContributionStats($this);

      // Trigger any necessary events or notifications
      $this->handleSuccessfulPayment();

      return $this;
    });
  }

  public function markAsPending()
  {
    return $this->update([
      'status' => 'pending',
      'is_verified' => false
    ]);
  }

  public function markAsOverdue()
  {
    return $this->update([
      'status' => 'overdue'
    ]);
  }

  // Enhanced Metadata Management
  public function addMetadata(string $key, $value)
  {
    $metadata = $this->metadata ?? [];
    $metadata[$key] = $value;
    $this->update(['metadata' => $metadata]);
    return $this;
  }

  public function getMetadata(string $key, $default = null)
  {
    return data_get($this->metadata, $key, $default);
  }

  // Computed Attributes
  public function getDaysOverdueAttribute(): int
  {
    return $this->isOverdue()
      ? now()->diffInDays($this->contribution_date)
      : 0;
  }

  // Static Methods for Contribution Management
  public static function createContribution(
    GroupMember $groupMember,
    float       $amount,
    ?Carbon     $contributionDate = null,
    string      $type = 'regular'
  ): self
  {
    return DB::transaction(function () use ($groupMember, $amount, $contributionDate, $type) {
      $contribution = self::create([
        'group_member_id' => $groupMember->id,
        'group_id' => $groupMember->group_id,
        'user_id' => $groupMember->user_id,
        'amount' => $amount,
        'contribution_date' => $contributionDate ?? now(),
        'type' => $type,
        'status' => 'pending'
      ]);

      // Trigger contribution created event
      $contribution->handleNewContribution();

      return $contribution;
    });
  }

  // Penalty Calculation
  public function calculateOverduePenalty(float $penaltyRate = 0.01): float
  {
    if (!$this->isOverdue()) {
      return 0;
    }

    return $this->amount * $this->daysOverdue * $penaltyRate;
  }

  // Additional Business Logic Methods
  public function applyOverduePenalty()
  {
    if (!$this->isOverdue()) {
      return $this;
    }

    $penalty = $this->calculateOverduePenalty();

    return DB::transaction(function () use ($penalty) {
      // Create a new penalty contribution
      $penaltyContribution = self::create([
        'group_member_id' => $this->group_member_id,
        'group_id' => $this->group_id,
        'user_id' => $this->user_id,
        'amount' => $penalty,
        'contribution_date' => now(),
        'type' => 'penalty',
        'status' => 'pending'
      ]);

      return $penaltyContribution;
    });
  }

  // Event Handling Methods
  protected function handleNewContribution()
  {
    // Trigger events, send notifications, etc.
    // Example:
    // event(new ContributionCreatedEvent($this));
  }

  protected function handleSuccessfulPayment()
  {
    // Handle successful payment logic
    // Example:
    // event(new ContributionPaidEvent($this));

    // Update group's total contributions
    $this->group->incrementTotalContributions($this->amount);
  }

  // Validation Methods
  public function validateContribution()
  {
    // Perform validation checks
    if ($this->amount <= 0) {
      throw new \InvalidArgumentException("Contribution amount must be positive");
    }

    // Additional validation logic can be added here
    return true;
  }

  // Reporting and Analytics Methods
  public static function getMemberContributionSummary(GroupMember $groupMember)
  {
    return self::where('group_member_id', $groupMember->id)
      ->selectRaw('
        COUNT(*) as total_contributions,
        SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN status = "overdue" THEN amount ELSE 0 END) as total_overdue,
        AVG(CASE WHEN status = "paid" THEN amount END) as average_contribution
      ')
      ->first();
  }

  // Contribution Reconciliation
  public function reconcile(bool $isVerified = true)
  {
    return DB::transaction(function () use ($isVerified) {
      $this->update([
        'is_verified' => $isVerified,
        'status' => $isVerified ? 'paid' : 'pending'
      ]);

      // Additional reconciliation logic
      if ($isVerified) {
        $this->handleSuccessfulPayment();
      }

      return $this;
    });
  }

  // Advanced Reporting Methods
  public static function getGroupContributionAnalytics(Group $group)
  {
    return self::where('group_id', $group->id)
      ->selectRaw('
                COUNT(*) as total_contributions,
                SUM(amount) as total_amount,
                AVG(amount) as average_contribution,
                COUNT(DISTINCT user_id) as contributing_members,
                SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = "overdue" THEN amount ELSE 0 END) as total_overdue,
                COUNT(CASE WHEN status = "overdue" THEN 1 END) as overdue_count
            ')
      ->first();
  }

  // Contribution Projection and Forecasting
  public function forecastContributionCompletion()
  {
    $groupMember = $this->groupMember;
    $group = $this->group;

    $totalContributions = $group->duration_months;
    $paidContributions = self::where('group_member_id', $groupMember->id)
      ->where('status', 'paid')
      ->count();

    $remainingContributions = $totalContributions - $paidContributions;
    $averageContributionAmount = self::where('group_member_id', $groupMember->id)
      ->where('status', 'paid')
      ->avg('amount');

    return [
      'total_contributions' => $totalContributions,
      'paid_contributions' => $paidContributions,
      'remaining_contributions' => $remainingContributions,
      'average_contribution_amount' => $averageContributionAmount,
      'estimated_completion_date' => now()->addMonths($remainingContributions)
    ];
  }

  // Contribution Compliance Check
  public function checkComplianceStatus()
  {
    $groupMember = $this->groupMember;
    $group = $this->group;

    $totalExpectedContributions = $group->duration_months;
    $paidContributions = self::where('group_member_id', $groupMember->id)
      ->where('status', 'paid')
      ->count();

    $compliancePercentage = ($paidContributions / $totalExpectedContributions) * 100;

    return [
      'is_compliant' => $compliancePercentage >= 80, // 80% compliance threshold
      'compliance_percentage' => $compliancePercentage,
      'total_expected' => $totalExpectedContributions,
      'total_paid' => $paidContributions
    ];
  }

  // Contribution Recovery and Makeup Mechanism
  public function createMakeupContribution()
  {
    // Create a makeup contribution for missed or overdue contributions
    return self::create([
      'group_member_id' => $this->group_member_id,
      'group_id' => $this->group_id,
      'user_id' => $this->user_id,
      'amount' => $this->amount,
      'contribution_date' => now(),
      'type' => 'makeup',
      'status' => 'pending'
    ]);
  }

  // Contribution Notification Preferences
  public function sendContributionNotification(string $type = 'reminder')
  {
    // Placeholder for notification logic
    // Could integrate with a notification service
    $notificationMethods = $this->groupMember->user->notification_preferences;

    $notificationDetails = [
      'contribution' => $this,
      'group' => $this->group,
      'type' => $type
    ];

    // Example notification types
    match($type) {
      'reminder' => $this->sendReminderNotification($notificationDetails),
      'overdue' => $this->sendOverdueNotification($notificationDetails),
      'success' => $this->sendSuccessNotification($notificationDetails),
      default => null
    };
  }

  // Bulk Contribution Operations
  public static function bulkMarkOverdue()
  {
    return self::where('status', 'pending')
      ->where('contribution_date', '<', now())
      ->update(['status' => 'overdue']);
  }

  public static function bulkSendReminders()
  {
    $pendingContributions = self::where('status', 'pending')
      ->where('contribution_date', '<=', now()->addDays(7))
      ->get();

    foreach ($pendingContributions as $contribution) {
      $contribution->sendContributionNotification('reminder');
    }
  }

  // Export and Reporting Methods
  public function toExportArray()
  {
    return [
      'id' => $this->uuid,
      'group_name' => $this->group->name,
      'member_name' => $this->user->name,
      'amount' => $this->amount,
      'contribution_date' => $this->contribution_date->format('Y-m-d'),
      'status' => $this->status,
      'type' => $this->type
    ];
  }

  // Private Notification Helper Methods
  private function sendReminderNotification(array $details)
  {
    // Implement reminder notification logic
  }

  private function sendOverdueNotification(array $details)
  {
    // Implement overdue notification logic
  }

  private function sendSuccessNotification(array $details)
  {
    // Implement success notification logic
  }
}
