<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Loan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContributionsExport;
use App\Exports\LoansExport;

class ReportingController extends Controller
{
  public function exportContributions(Request $request)
  {
    $contributions = Contribution::where('group_member_id', $request->user()->groupMember->id)->get();
    return Excel::download(new ContributionsExport($contributions), 'contributions.xlsx');
  }

  public function exportLoans(Request $request)
  {
    $loans = Loan::where('group_member_id', $request->user()->groupMember->id)->get();
    return Excel::download(new LoansExport($loans), 'loans.xlsx');
  }

  public function exportContributionsPDF(Request $request)
  {
    $contributions = Contribution::where('group_member_id', $request->user()->groupMember->id)->get();
    $pdf = Pdf::loadView('reports.contributions', compact('contributions'));
    return $pdf->download('contributions.pdf');
  }

  public function exportLoansPDF(Request $request)
  {
    $loans = Loan::where('group_member_id', $request->user()->groupMember->id)->get();
    $pdf = Pdf::loadView('reports.loans', compact('loans'));
    return $pdf->download('loans.pdf');
  }
}
