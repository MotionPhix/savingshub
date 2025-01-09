<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
  use AuthorizesRequests;

  public function __construct(protected LoanService $loanService) {}

  /*public function index()
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
  }*/

  /**
   * Display loans for a specific group
   */
  public function index(Group $group)
  {
    $this->authorize('view loans');

    $loans = $group->loans()->with([
      'groupMember.user',
      'approvedBy'
    ])->paginate(10);

    return Inertia('Loans/Index', [
      'group' => $group,
      'loans' => $loans,
      'canCreateLoan' => $this->authorize('create loan')
    ]);
  }

  /**
   * Show loan creation form
   */
  public function create(Request $request, Group $group)
  {
    $this->authorize('create loan');

    // Calculate loan eligibility and limits
    $loanEligibility = $this->loanService->calculateLoanEligibility(
      $request->user(),
      $group
    );

    return Inertia('Loans/Create', [
      'group' => $group,
      'loanEligibility' => $loanEligibility
    ]);
  }

  /**
   * Store a new loan request
   */
  public function store(Request $request, Group $group)
  {
    $this->authorize('create loan');

    $validatedData = $request->validate([
      'amount' => [
        'required',
        'numeric',
        'min:100',
        "max:{$group->max_loan_amount}"
      ],
      'purpose' => 'nullable|string|max:500',
      'supporting_documents' => 'array|max:5',
      'supporting_documents.*' => 'file|max:5120' // 5MB max
    ]);

    try {
      $loan = $this->loanService->createLoanRequest(
        $request->user(),
        $group,
        $validatedData
      );

      return redirect()->route('groups.loans.show', [$group, $loan])
        ->with('success', 'Loan request submitted successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Show detailed loan information
   */
  public function show(Group $group, Loan $loan)
  {
    $this->authorize('view loan', $loan);

    $loan->load([
      'groupMember.user',
      'approvedBy',
      'group'
    ]);

    return Inertia('Loans/Show', [
      'loan' => $loan,
      'canApproveLoan' => $this->authorize('approve loan'),
      'canMakeLoanPayment' => $this->authorize('make loan payment')
    ]);
  }

  /**
   * Approve a loan request
   */
  public function approve(Request $request, Group $group, Loan $loan)
  {
    $this->authorize('approve loan');

    $validatedData = $request->validate([
      'approval_notes' => 'nullable|string|max:500'
    ]);

    try {
      $approvedLoan = $this->loanService->approveLoanRequest(
        $request->user(),
        $loan,
        $validatedData['approval_notes'] ?? null
      );

      return redirect()->route('groups.loans.show', [$group, $approvedLoan])
        ->with('success', 'Loan approved successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Reject a loan request
   */
  public function reject(Request $request, Group $group, Loan $loan)
  {
    $this->authorize('approve loan', $loan);

    $validatedData = $request->validate([
      'rejection_reason' => 'required|string|max:500'
    ]);

    try {
      $rejectedLoan = $this->loanService->rejectLoanRequest(
        $request->user(),
        $loan,
        $validatedData['rejection_reason']
      );

      return redirect()->route('groups.loans.show', [$group, $rejectedLoan])
        ->with('success', 'Loan request rejected');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Make a loan payment
   */
  public function makePayment(Request $request, Group $group, Loan $loan)
  {
    $this->authorize('make loan payment', $loan);

    $validatedData = $request->validate([
      'payment_amount' => [
        'required',
        'numeric',
        'min:1',
        "max:{$loan->total_amount}"
      ],
      'payment_method' => 'required|in:bank_transfer,mobile_money,cash'
    ]);

    try {
      $updatedLoan = $this->loanService->makeLoanPayment(
        $request->user(),
        $loan,
        $validatedData['payment_amount'],
        $validatedData['payment_method']
      );

      return redirect()->route('groups.loans.show', [$group, $updatedLoan])
        ->with('success', 'Loan payment recorded successfully');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }

  /**
   * Generate loan repayment schedule
   */
  public function repaymentSchedule(Group $group, Loan $loan)
  {
    $this->authorize('view loan', $loan);

    $schedule = $this->loanService->generateRepaymentSchedule($loan);

    return Inertia('Loans/RepaymentSchedule', [
      'loan' => $loan,
      'schedule' => $schedule
    ]);
  }

  /**
   * List overdue loans
   */
  public function overdueLoans(Group $group)
  {
   $this->authorize('manage loans', $group);

    $overdueLoans = $loan = $group->loans()
      ->where('status', 'overdue')
      ->with(['groupMember.user'])
      ->paginate(10);

    return Inertia('Loans/Overdue', [
      'group' => $group,
      'overdueLoans' => $overdueLoans
    ]);
  }

  /**
   * Handle loan default
   */
  public function handleDefault(Group $group, Loan $loan)
  {
    $this->authorize('manage loan default', $loan);

    try {
      $defaultedLoan = $this->loanService->processLoanDefault($loan);

      return redirect()->route('groups.loans.show', [$group, $defaultedLoan])
        ->with('warning', 'Loan has been marked as defaulted');
    } catch (\Exception $e) {
      return back()->withErrors(['message' => $e->getMessage()]);
    }
  }
}
