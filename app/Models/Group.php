<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Services\GroupActivityService;
use App\Services\LoanInterest\FixedInterestCalculator;
use App\Services\LoanInterest\TieredInterestCalculator;
use App\Services\LoanInterest\VariableInterestCalculator;
use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class Group extends Model
{
  use HasFactory, BootUuid, SoftDeletes;

  protected $fillable = [
    'name',
    'slug',
    'description',
    'mission_statement',
    'contribution_frequency',
    'contribution_amount',
    'duration_months',
    'start_date',
    'end_date',
    'status',
    'is_public',
    'allow_member_invites',
    'created_by',
    'loan_interest_type',
    'base_interest_rate',
    'interest_tiers',
    'max_loan_amount',
    'loan_duration_months',
    'require_group_approval',
    'settings',
    'notification_preferences'
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'is_public' => 'boolean',
    'allow_member_invites' => 'boolean',
    'require_group_approval' => 'boolean',
    'interest_tiers' => 'array',
    'settings' => 'array',
    'notification_preferences' => 'array'
  ];

  protected $appends = [
    'pending_contributions_count',
    'pending_loan_requests_count',
    'pending_invitations_count'
  ];

  // Mutator to ensure proper formatting of interest tiers
  public function setInterestTiersAttribute($value)
  {
    // Ensure tiers are sorted and formatted consistently
    if (is_array($value)) {
      usort($value, function ($a, $b) {
        return $a['min_amount'] <=> $b['min_amount'];
      });
    }

    $this->attributes['interest_tiers'] = json_encode($value);
  }

  // Accessor to return formatted tiers
  public function getInterestTiersAttribute($value)
  {
    return $value ? json_decode($value, true) : [];
  }

  // Method to calculate interest based on tiered rates
  public function calculateTieredInterest(float $loanAmount)
  {
    if ($this->loan_interest_type !== 'tiered' || empty($this->interest_tiers)) {
      return $this->base_interest_rate * $loanAmount;
    }

    $totalInterest = 0;
    foreach ($this->interest_tiers as $tier) {
      // Calculate interest for the portion of loan in this tier
      $tierStart = $tier['min_amount'];
      $tierEnd = $tier['max_amount'];
      $tierRate = $tier['rate'] / 100;

      // Determine the amount in this tier
      $tierAmount = min($loanAmount, $tierEnd) - $tierStart;

      if ($tierAmount > 0) {
        $totalInterest += $tierAmount * $tierRate;
      }

      // Move to next tier if loan amount exceeds current tier
      if ($loanAmount <= $tierEnd) {
        break;
      }
    }

    return $totalInterest;
  }

  // Boot method for automatic slug generation
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($group) {
      $group->slug = Str::slug($group->name);
    });

    static::updating(function ($group) {
      if ($group->isDirty('name')) {
        $group->slug = Str::slug($group->name);
      }
    });
  }

  // Relationships
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function members(): HasMany
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
    return $query->where('status', 'active');
  }

  public function scopeActiveMembership($query)
  {
    return $query->whereHas('members', function($q) {
      $q->where('status', 'active')
        ->where('user_id', auth()->id());
    });
  }

  public function getUserRoleAttribute()
  {
    $membership = $this->members->first(fn($member) => $member->user_id === auth()->id());
    return $membership ? $membership->role : null;
  }

  public function scopePublic($query)
  {
    return $query->where('is_public', true);
  }

  // Add relationship
  public function activities()
  {
    return $this->hasMany(GroupActivity::class);
  }

  // Helper methods to log activities
  public function logMemberJoined(User $user)
  {
    $this->logActivity('member_joined', $user);
  }

  public function logContributionMade(User $user, float $amount)
  {
    $this->logActivity('contribution_made', $user, [
      'amount' => $amount
    ]);
  }

  public function logLoanRequested(User $user, float $amount)
  {
    $this->logActivity('loan_requested', $user, [
      'amount' => $amount
    ]);
  }

  private function logActivity(
    string $type,
    ?User $user = null,
    ?array $metadata = null
  ) {
    app(GroupActivityService::class)->log(
      $this,
      $type,
      $user,
      null,
      $metadata
    );
  }

  // Utility Methods
  public function getTotalContributions(): float
  {
    return $this->contributions()->sum('amount');
  }

  public function getTotalLoans(): float
  {
    return $this->loans()->sum('amount');
  }

  public function getMemberCount(): int
  {
    return $this->members()->count();
  }

  public function isExpired(): bool
  {
    return $this->end_date && now()->isAfter($this->end_date);
  }

  public function canAcceptNewMembers(): bool
  {
    return $this->status === 'active'
      && $this->allow_member_invites
      && (!$this->end_date || now()->isBefore($this->end_date));
  }

  // Updated interest calculation methods
  public function calculateInterest(float $loanAmount, ?User $user = null): float
  {
    // If no user is provided, use a default or throw an exception
    if (!$user) {
      $user = $this->creator; // Fallback to group creator
    }

    // Use factory method or dependency injection to select the appropriate calculator
    $calculator = $this->getInterestCalculator();

    return $calculator->calculateInterest($this, $loanAmount, $user);
  }

  private function getInterestCalculator()
  {
    return match($this->loan_interest_type) {
      'fixed' => App::make(FixedInterestCalculator::class),
      'variable' => App::make(VariableInterestCalculator::class),
      'tiered' => App::make(TieredInterestCalculator::class),
      default => throw new \InvalidArgumentException("Invalid interest calculation type")
    };
  }

  // Enhanced loan calculation method
  public function calculateLoanDetails(float $loanAmount, ?User $user = null): array
  {
    // Validate loan amount against group limits
    $this->validateLoanAmount($loanAmount);

    // Calculate interest
    $interestAmount = $this->calculateInterest($loanAmount, $user);

    // Calculate total loan amount
    $totalLoanAmount = $loanAmount + $interestAmount;

    // Calculate monthly payment
    $monthlyPayment = $this->calculateMonthlyPayment(
      $totalLoanAmount,
      $this->loan_duration_months
    );

    return [
      'principal_amount' => $loanAmount,
      'interest_amount' => $interestAmount,
      'interest_rate' => $this->getCurrentInterestRate(),
      'total_loan_amount' => $totalLoanAmount,
      'loan_duration_months' => $this->loan_duration_months,
      'monthly_payment' => $monthlyPayment,
      'first_payment_date' => $this->calculateFirstPaymentDate()
    ];
  }

  private function validateLoanAmount(float $loanAmount)
  {
    // Check against maximum loan amount
    if ($this->max_loan_amount && $loanAmount > $this->max_loan_amount) {
      throw new \InvalidArgumentException(
        "Loan amount exceeds group's maximum limit of {$this->max_loan_amount}"
      );
    }

    // Additional validation can be added here
  }

  private function calculateMonthlyPayment(float $totalLoanAmount, int $duration): float
  {
    // Standard amortization calculation
    if ($duration <= 0) {
      throw new \InvalidArgumentException("Loan duration must be positive");
    }

    // Simple equal principal payment method
    return round($totalLoanAmount / $duration, 2);
  }

  private function getCurrentInterestRate(): float
  {
    // Return the current applicable interest rate based on the group's interest type
    return match($this->loan_interest_type) {
      'fixed' => $this->base_interest_rate,
      'variable' => $this->base_interest_rate, // In a real scenario, this would be dynamically calculated
      'tiered' => $this->base_interest_rate, // Similarly, this would use tier logic
      default => 5.00 // Fallback rate
    };
  }

  private function calculateFirstPaymentDate(): \Carbon\Carbon
  {
    // Calculate the first payment date
    return now()->addMonth();
  }

  // Additional utility methods for loan management
  public function canRequestLoan(User $user): bool
  {
    // Check if loans are allowed
    if (!$this->canAcceptNewMembers()) {
      return false;
    }

    // Check if user is a group member
    $membership = $this->members()->where('user_id', $user->id)->first();
    if (!$membership) {
      return false;
    }

    // Additional checks can be added here
    return true;
  }

  // Computed attributes
  public function getPendingContributionsCountAttribute()
  {
    return Contribution::query()
      ->whereHas('groupMember', function ($query) {
        $query->where('group_id', $this->id)
          ->where('group_members.status', 'active'); // Ensure active group members
      })
      ->where('contributions.status', 'pending')
      ->count();
  }

  /*public function getPendingLoanRequestsCountAttribute()
  {
    return Loan::query()
      ->join('group_members', 'loans.group_member_id', '=', 'group_members.id')
      ->where('group_members.group_id', $this->id)
      ->where('loans.status', 'pending')
      ->count();
  }*/

  public function getPendingLoanRequestsCountAttribute()
  {
    return Loan::pendingInGroup($this->id)->count();
  }

  /*public function getPendingInvitationsCountAttribute()
  {
    return $this->members()->wherePivot('status', 'invited')->count();
  }*/

  public function getPendingInvitationsCountAttribute()
  {
    return $this->members()
      ->where('status', 'invited')
      ->count();
  }

  public function getFormattedContributionAmount(): string
  {
    return app(CurrencyService::class)->formatCurrency(
      $this->contribution_amount,
      $this
    );
  }

  public function getFormattedMaxLoanAmount(): ?string
  {
    return $this->max_loan_amount
      ? app(CurrencyService::class)->formatCurrency(
        $this->max_loan_amount,
        $this
      )
      : null;
  }
}
