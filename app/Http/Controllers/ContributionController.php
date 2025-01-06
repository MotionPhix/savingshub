<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributionController extends Controller
{
  public function index()
  {
    $contributions = Contribution::where('group_member_id', Auth::user()->groupMember->id)->get();
    return view('contributions.index', compact('contributions'));
  }

  public function create()
  {
    return view('contributions.create');
  }

  public function store(Request $request)
  {
    $request->validate([
      'amount' => 'required|numeric',
      'contribution_date' => 'required|date',
    ]);

    Contribution::create([
      'group_member_id' => Auth::user()->groupMember->id,
      'amount' => $request->amount,
      'contribution_date' => $request->contribution_date,
    ]);

    return redirect()->route('contributions.index')->with('success', 'Contribution added successfully.');
  }
}
