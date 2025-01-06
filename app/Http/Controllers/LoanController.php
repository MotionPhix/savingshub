<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
  use AuthorizesRequests;

  public function index()
  {
    $loans = Loan::where('group_member_id', Auth::user()->groupMember->id)->get();
    return view('loans.index', compact('loans'));
  }

  public function create()
  {
    return view('loans.create');
  }

  public function store(Request $request, Group $group, User $user, float $loanAmount)
  {
    $request->validate([
      'amount' => 'required|numeric',
      'interest_amount' => 'required|numeric',
      'loan_date' => 'required|date',
      'due_date' => 'required|date',
    ]);

    // Validate loan request
    if (!$group->canRequestLoan($user)) {
      throw new \Exception("Loan request is not allowed");
    }

    // Calculate loan details
    $loanDetails = $group->calculateLoanDetails($loanAmount, $user);

    // Create loan record
    $loan = Loan::create([
      'group_id' => $group->id,
      'user_id' => $user->id,
      'principal_amount' => $loanDetails['principal_amount'],
      'interest_amount' => $loanDetails['interest_amount'],
      'total_amount' => $loanDetails['total_loan_amount'],
      'interest_rate' => $loanDetails['interest_rate'],
      'duration_months' => $loanDetails['loan_duration_months'],
      'monthly_payment' => $loanDetails['monthly_payment'],
      'first_payment_date' => $loanDetails['first_payment_date'],
      'status' => $group->require_group_approval ? 'pending' : 'approved'
    ]);

    return redirect()->route('loans.index')->with('success', 'Loan created successfully.');
  }

  public function approve(Request $request, Loan $loan)
  {
    // Ensure only group admins can approve
    $this->authorize('approve loan', $loan->group);

    $request->validate([
      'status' => 'required|in:approved,rejected'
    ]);

    DB::transaction(function () use ($request, $loan) {
      if ($request->status === 'approved') {
        $loan->update([
          'status' => 'active',
          'approved_at' => now(),
          'approved_by' => auth()->id()
        ]);

        // Potential additional logic like fund disbursement
      } else {
        $loan->update([
          'status' => 'rejected',
          'rejected_at' => now(),
          'rejected_by' => auth()->id()
        ]);
      }
    });

    if ($request->wantsJson()) {
      return response()->json([
        'message' => 'Loan ' . $request->status,
        'loan' => $loan
      ]);
    }

    return back()->with([
      'flush' => 'Your loan application was ' . $request->status
    ]);
  }
}
