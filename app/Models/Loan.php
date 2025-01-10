<?php

namespace App\Models;

use App\Traits\BootUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
  use HasFactory, BootUuid, SoftDeletes;

  protected $fillable = [
    'group_member_id',
    'group_id',
    'user_id',
    'principal_amount',
    'interest_amount',
    'total_amount',
    'interest_rate',
    'loan_date',
    'due_date',
    'first_payment_date',
    'duration_months',
    'status',
    'total_paid_amount',
    'missed_payments',
    'approved_by',
    'approved_at',
    'approval_notes',
    'monthly_payment',
    'payment_schedule',
    'metadata'
  ];

  protected $casts = [
    'principal_amount' => 'decimal:2',
    'interest_amount' => 'decimal:2',
    'total_amount' => 'decimal:2',
    'interest_rate' => 'decimal:2',
    'loan_date' => 'date',
    'due_date' => 'date',
    'first_payment_date' => 'date',
    'approved_at' => 'datetime',
    'total_paid_amount' => 'decimal:2',
    'monthly_payment' => 'decimal:2',
    'payment_schedule' => 'array',
    'metadata' => 'array'
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

  public function approvedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeOverdue($query)
  {
    return $query->where('status', 'overdue')
      ->where('due_date', '<', now());
  }

  // Status Checks
  public function isActive(): bool
  {
    return $this->status === 'active';
  }

  public function isPaid(): bool
  {
    return $this->status === 'paid';
  }

  public function isOverdue(): bool
  {
    return $this->status === 'overdue' ||
      ($this->status === 'active' && now()->isAfter($this->due_date));
  }

  // Loan Lifecycle Management
  public function approve(User $approver, ?string $notes = null)
  {
    $this->update([
      'status' => 'active',
      'approved_by' => $approver->id,
      'approved_at' => now(),
      'approval_notes' => $notes,
      'first_payment_date' => $this->calculateFirstPaymentDate()
    ]);

    // Generate payment schedule
    $this->generatePaymentSchedule();
  }

  public function reject(User $approver, string $reason)
  {
    $this->update([
      'status' => 'rejected',
      'approved_by' => $approver->id,
      'approval_notes' => $reason
    ]);
  }

  public function markAsPaid()
  {
    $this->update([
      'status' => 'paid',
      'total_paid_amount' => $this->total_amount
    ]);
  }

  // Payment Handling
  public function recordPayment(float $amount)
  {
    $newTotalPaid = $this->total_paid_amount + $amount;

    $this->update([
      'total_paid_amount' => $newTotalPaid
    ]);

    // Check if fully paid
    if ($newTotalPaid >= $this->total_amount) {
      $this->markAsPaid();
    }
  }

  // Payment Schedule Generation
  private function generatePaymentSchedule()
  {
    $paymentSchedule = [];
    $remainingAmount = $this->total_amount;
    $paymentDate = $this->first_payment_date;

    for ($i = 0; $i < $this->duration_months; $i++) {
      $paymentSchedule[] = [
        'payment_number' => $i + 1,
        'due_date' => $paymentDate,
        'amount' => $this->monthly_payment,
        'status' => 'pending'
      ];

      $paymentDate = $paymentDate->addMonth();
      $remainingAmount -= $this->monthly_payment;
    }

    $this->update(['payment_schedule' => $paymentSchedule]);
  }

  private function calculateFirstPaymentDate(): Carbon
  {
    return $this->loan_date->addMonth(); // Assuming first payment is due one month after loan date
  }

  // Penalty Calculation
  public function calculateOverduePenalty(float $penaltyRate = 0.01): float
  {
    if (!$this->isOverdue()) {
      return 0;
    }

    return ($this->total_amount - $this->total_paid_amount) * $penaltyRate;
  }

  public function scopePendingInGroup($query, $groupId)
  {
    return $query->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
      ->where('group_members.group_id', $groupId)
      ->where('loans.status', 'pending');
  }
}
