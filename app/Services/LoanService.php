<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanInterest\LoanInterestCalculatorFactory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanService
{
  /**
   * Calculate loan eligibility for a user
   */
  public function calculateLoanEligibility(User $user, Group $group): array
  {
    // Retrieve user's group membership
    $groupMember = $group->members()
      ->where('user_id', $user->id)
      ->first();

    if (!$groupMember) {
      throw new \Exception("User is not a member of this group");
    }

    // Calculate total contributions
    $totalContributions = $groupMember->contributions()->sum('amount');

    // Calculate existing loan details
    $existingLoans = $groupMember->loans()
      ->whereIn('status', ['active', 'pending'])
      ->sum('total_amount');

    // Determine maximum loan amount based on contributions and group rules
    $maxLoanAmount = min(
      $group->max_loan_amount ?? 10000, // Default max if not set
      $totalContributions * 3, // 3x total contributions
      $group->contribution_amount * 12 // Yearly contribution equivalent
    );

    return [
      'total_contributions' => $totalContributions,
      'existing_loans' => $existingLoans,
      'max_loan_amount' => $maxLoanAmount,
      'available_loan_amount' => max(0, $maxLoanAmount - $existingLoans),
      'contribution_ratio' => $totalContributions > 0
        ? $existingLoans / $totalContributions
        : 0
    ];
  }

  /**
   * Create a new loan request
   */
  public function createLoanRequest(
    User  $user,
    Group $group,
    array $data
  ): Loan
  {
    return DB::transaction(function () use ($user, $group, $data) {
      // Get group member
      $groupMember = $group->members()
        ->where('user_id', $user->id)
        ->firstOrFail();

      // Calculate loan details
      $interestCalculator = LoanInterestCalculatorFactory::create($group);
      $interestAmount = $interestCalculator->calculateInterest(
        $group,
        $data['amount'],
        $user
      );

      // Create loan
      $loan = Loan::create([
        'group_member_id' => $groupMember->id,
        'group_id' => $group->id,
        'user_id' => $user->id,
        'principal_amount' => $data['amount'],
        'interest_amount' => $interestAmount,
        'total_amount' => $data['amount'] + $interestAmount,
        'interest_rate' => $group->base_interest_rate,
        'loan_date' => now(),
        'due_date' => now()->addMonths($group->loan_duration_months),
        'status' => $group->require_group_approval ? 'pending' : 'active',
        'duration_months' => $group->loan_duration_months,
        'monthly_payment' => $this->calculateMonthlyPayment(
          $data['amount'] + $interestAmount,
          $group->loan_duration_months
        ),
        'metadata' => [
          'purpose' => $data['purpose'] ?? null
        ]
      ]);

      // Handle supporting documents if provided
      if (isset($data['supporting_documents'])) {
        $this->uploadSupportingDocuments($loan, $data['supporting_documents']);
      }

      // Notify group admins if approval is required
      if ($group->require_group_approval) {
        $this->notifyGroupAdmins($loan);
      }

      return $loan;
    });
  }

  /**
   * Approve a loan request
   */
  public function approveLoanRequest(
    User    $approver,
    Loan    $loan,
    ?string $approvalNotes = null
  ): Loan
  {
    return DB::transaction(function () use ($approver, $loan, $approvalNotes) {
      // Validate loan can be approved
      if ($loan->status !== 'pending') {
        throw new \Exception("Loan cannot be approved in current status");
      }

      $loan->update([
        'status' => 'active',
        'approved_by' => $approver->id,
        'approved_at' => now(),
        'approval_notes' => $approvalNotes
      ]);

      // Generate payment schedule
      $this->generateRepaymentSchedule($loan);

      return $loan;
    });
  }

  /**
   * Reject a loan request
   */
  public function rejectLoanRequest(
    User   $approver,
    Loan   $loan,
    string $rejectionReason
  ): Loan
  {
    return DB::transaction(function () use ($approver, $loan, $rejectionReason) {
      // Validate loan can be rejected
      if ($loan->status !== 'pending') {
        throw new \Exception("Loan cannot be rejected in current status");
      }

      $loan->update([
        'status' => 'rejected',
        'approved_by' => $approver->id,
        'approved_at' => now(),
        'approval_notes' => $rejectionReason
      ]);

      return $loan;
    });
  }

  /**
   * Make a loan payment
   */
  public function makeLoanPayment(
    User   $user,
    Loan   $loan,
    float  $paymentAmount,
    string $paymentMethod
  ): Loan
  {
    return DB::transaction(function () use ($user, $loan, $paymentAmount, $paymentMethod) {
      // Validate payment
      if ($loan->status !== 'active') {
        throw new \Exception("Loan is not in an active state");
      }

      if ($paymentAmount > $loan->total_amount - $loan->total_paid_amount) {
        throw new \Exception("Payment amount exceeds remaining loan balance");
      }

      // Record payment
      $loan->update([
        'total_paid_amount' => $loan->total_paid_amount + $paymentAmount,
        'last_payment_date' => now()
      ]);

      // Create payment record
      $loan->payments()->create([
        'amount' => $paymentAmount,
        'payment_method' => $paymentMethod,
        'user_id' => $user->id
      ]);

      // Check if loan is fully paid
      if ($loan->total_paid_amount >= $loan->total_amount) {
        $loan->update(['status' => 'paid']);
      }

      return $loan;
    });
  }

  /**
   * Generate loan repayment schedule
   */
  public function generateRepaymentSchedule(Loan $loan): array
  {
    $schedule = [];
    $remainingBalance = $loan->total_amount;
    $paymentDate = $loan->first_payment_date ?? now()->addMonth();

    for ($i = 1; $i <= $loan->duration_months; $i++) {
      $monthlyPayment = $loan->monthly_payment;
      $interestPayment = $remainingBalance * ($loan->interest_rate / 12 / 100);
      $principalPayment = $monthlyPayment - $interestPayment;

      $schedule[] = [
        'payment_number' => $i,
        'due_date' => $paymentDate,
        'total_payment' => $monthlyPayment,
        'principal_payment' => $principalPayment,
        'interest_payment' => $interestPayment,
        'remaining_balance' => max(0, $remainingBalance - $principalPayment)
      ];

      $remainingBalance -= $principalPayment;
      $paymentDate = $paymentDate->addMonth();
    }

    return $schedule;
  }

  /**
   * Process loan default
   */
  public function processLoanDefault(Loan $loan): Loan
  {
    return DB::transaction(function () use ($loan) {
      // Check if loan is eligible for default
      if (!$this->isLoanEligibleForDefault($loan)) {
        throw new \Exception("Loan is not eligible for default processing");
      }

      // Update loan status
      $loan->update([
        'status' => 'defaulted',
        'defaulted_at' => now()
      ]);

      // Trigger default penalty calculation
      $penalty = $this->calculateDefaultPenalty($loan);

      // Create default record
      $loan->defaults()->create([
        'original_amount' => $loan->total_amount,
        'remaining_amount' => $loan->total_amount - $loan->total_paid_amount,
        'penalty_amount' => $penalty,
        'default_date' => now()
      ]);

      // Notify group admins and user
      $this->notifyLoanDefault($loan, $penalty);

      return $loan;
    });
  }

  /**
   * Check if loan is eligible for default
   */
  private function isLoanEligibleForDefault(Loan $loan): bool
  {
    // Criteria for loan default
    return $loan->status === 'overdue' &&
      now()->diffInMonths($loan->due_date) >= 3 &&
      $loan->total_paid_amount < $loan->total_amount * 0.5;
  }

  /**
   * Calculate default penalty
   */
  private function calculateDefaultPenalty(Loan $loan): float
  {
    $remainingBalance = $loan->total_amount - $loan->total_paid_amount;
    $penaltyRate = 0.1; // 10% default penalty

    return $remainingBalance * $penaltyRate;
  }

  /**
   * Upload supporting documents for loan
   */
  private function uploadSupportingDocuments(Loan $loan, array $documents): void
  {
    foreach ($documents as $document) {
      $path = $document->store('loan_documents/' . $loan->id, 'public');

      $loan->documents()->create([
        'file_path' => $path,
        'file_name' => $document->getClientOriginalName(),
        'mime_type' => $document->getMimeType(),
        'size' => $document->getSize()
      ]);
    }
  }

  /**
   * Notify group admins about loan request
   */
  private function notifyGroupAdmins(Loan $loan): void
  {
    // Get group admins
    $groupAdmins = $loan->group->members()
      ->where('role', 'admin')
      ->get();

    foreach ($groupAdmins as $admin) {
      // Send notification (can use Laravel's notification system)
      $admin->user->notify(new LoanRequestNotification($loan));
    }
  }

  /**
   * Notify about loan default
   */
  private function notifyLoanDefault(Loan $loan, float $penalty): void
  {
    // Notify loan user
    $loan->user->notify(new LoanDefaultNotification($loan, $penalty));

    // Notify group admins
    $groupAdmins = $loan->group->members()
      ->where('role', 'admin')
      ->get();

    foreach ($groupAdmins as $admin) {
      $admin->user->notify(new LoanDefaultAdminNotification($loan, $penalty));
    }
  }

  /**
   * Restructure overdue loan
   */
  public function restructureLoan(
    Loan  $loan,
    array $restructureOptions
  ): Loan
  {
    return DB::transaction(function () use ($loan, $restructureOptions) {
      // Validate restructuring options
      $this->validateLoanRestructure($loan, $restructureOptions);

      // Calculate new loan terms
      $newLoanTerms = $this->calculateRestructuredLoanTerms(
        $loan,
        $restructureOptions
      );

      // Update loan with new terms
      $loan->update([
        'total_amount' => $newLoanTerms['total_amount'],
        'duration_months' => $newLoanTerms['duration_months'],
        'monthly_payment' => $newLoanTerms['monthly_payment'],
        'restructured_at' => now(),
        'restructure_reason' => $restructureOptions['reason']
      ]);

      // Create restructure record
      $loan->restructures()->create([
        'original_amount' => $loan->total_amount,
        'original_duration' => $loan->duration_months,
        'new_amount' => $newLoanTerms['total_amount'],
        'new_duration' => $newLoanTerms['duration_months'],
        'restructure_reason' => $restructureOptions['reason']
      ]);

      // Regenerate payment schedule
      $this->generateRepaymentSchedule($loan);

      return $loan;
    });
  }

  /**
   * Validate loan restructure options
   */
  private function validateLoanRestructure(Loan $loan, array $options): void
  {
    // Validate restructure conditions
    if ($loan->status !== 'overdue') {
      throw new \Exception("Only overdue loans can be restructured");
    }

    // Validate restructure options
    $validOptions = [
      'extended_duration' => 'integer|min:1|max:24',
      'partial_payment' => 'numeric|min:0',
      'reason' => 'required|string|max:500'
    ];

    $validator = \Validator::make($options, $validOptions);

    if ($validator->fails()) {
      throw new \Exception("Invalid restructure options");
    }
  }

  /**
   * Calculate restructured loan terms
   */
  private function calculateRestructuredLoanTerms(
    Loan  $loan,
    array $restructureOptions
  ): array
  {
    $remainingBalance = $loan->total_amount - $loan->total_paid_amount;

    // Apply partial payment if provided
    if (isset($restructureOptions['partial_payment'])) {
      $remainingBalance -= $restructureOptions['partial_payment'];
    }

    // Extend loan duration
    $newDuration = $loan->duration_months +
      ($restructureOptions['extended_duration'] ?? 0);

    // Recalculate interest and monthly payment
    $interestCalculator = LoanInterestCalculatorFactory::create($loan->group);
    $newInterestAmount = $interestCalculator->calculateInterest(
      $loan->group,
      $remainingBalance,
      $loan->user
    );

    $totalAmount = $remainingBalance + $newInterestAmount;
    $monthlyPayment = $totalAmount / $newDuration;

    return [
      'total_amount' => $totalAmount,
      'duration_months' => $newDuration,
      'monthly_payment' => $monthlyPayment
    ];
  }
}
