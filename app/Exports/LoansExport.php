<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class LoansExport implements FromView
{
  protected $loans;

  public function __construct($loans)
  {
    $this->loans = $loans;
  }

  public function view(): \Illuminate\Contracts\View\View
  {
    return view('exports.loans', [
      'loans' => $this->loans
    ]);
  }
}
