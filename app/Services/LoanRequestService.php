<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\Loan;
use App\Services\LoanInterest\LoanInterestCalculatorFactory;
use Illuminate\Support\Facades\DB;

class LoanRequestService
{
  public function requestLoan(Group $group, User $user, float $amount)
  {
    // Validate loan request
    $this->validateLoanRequest($group, $user, $amount);

    // Calculate interest
    $interestCalculator = LoanInterestCalculatorFactory::create($group);
    $interestAmount = $interestCalculator->calculateInterest($group, $amount, $user);

    // Begin transaction
    return DB::transaction(function () use ($group, $user, $amount, $interestAmount) {
      // Create loan
      $loan = Loan::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'amount' => $amount,
        'interest_amount' => $interestAmount,
        'total_amount' => $amount + $interestAmount,
        'status' => $group->require_group_approval ? 'pending' : 'approved',
        'due_date' => now()->addMonths($group->loan_duration_months)
      ]);

      // Trigger notifications if group approval is required
      if ($group->require_group_approval) {
        $this->notifyGroupAdmins($group, $loan);
      }

      return $loan;
    });
  }

  private function validateLoanRequest(Group $group, User $user, float $amount)
  {
    // Check maximum loan amount
    if ($group->max_loan_amount && $amount > $group->max_loan_amount) {
      throw new \Exception("Loan amount exceeds group's maximum limit");
    }

    // Check existing active loans
    $activeLoans = Loan::where('user_id', $user->id)
      ->where('group_id', $group->id)
      ->where('status', 'active')
      ->count();

    if ($activeLoans > 0) {
      throw new \Exception("You have an existing active loan");
    }

    // Additional validation can be added here
  }

  private function notifyGroupAdmins(Group $group, Loan $loan)
  {
    // Notify group admins about pending loan
    $groupAdmins = $group->members()->where('role', 'admin')->get();

    foreach ($groupAdmins as $admin) {
      // Send notification (can use Laravel's notification system)
      $admin->user->notify(new PendingLoanRequestNotification($loan));
    }
  }
}
