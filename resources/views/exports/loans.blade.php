<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loans Report</title>
  <style>
    body {
      font-family: 'Arial', sans-serif;
      line-height: 1.6;
      color: #333;
    }
    .report-header {
      background-color: #f4f4f4;
      padding: 20px;
      margin-bottom: 20px;
      border-bottom: 2px solid #e0e0e0;
    }
    .report-title {
      font-size: 24px;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 10px;
    }
    .report-metadata {
      font-size: 14px;
      color: #7f8c8d;
    }
    .loans-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .loans-table thead {
      background-color: #e74c3c;
      color: white;
    }
    .loans-table th,
    .loans-table td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }
    .loans-table tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    .loans-table tr:hover {
      background-color: #ffebee;
    }
    .summary {
      margin-top: 20px;
      background-color: #f4f4f4;
      padding: 15px;
      border-radius: 5px;
    }
    .summary-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.8em;
      font-weight: bold;
    }
  </style>
</head>
<body>
<div class="report-header">
  <div class="report-title">Loans Report</div>
  <div class="report-metadata">
    <p>Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
    <p>Group: {{ $loans->first()->groupMember->group->name ?? 'N/A' }}</p>
  </div>
</div>

<table class="loans-table">
  <thead>
  <tr>
    <th>Loan ID</th>
    <th>Amount</th>
    <th>Interest</th>
    <th>Loan Date</th>
    <th>Due Date</th>
    <th>Status</th>
    <th>Total Repayment</th>
  </tr>
  </thead>
  <tbody>
  @foreach($loans as $loan)
    <tr>
      <td>{{ $loan->id }}</td>
      <td>{{ number_format($loan->amount, 2) }}</td>
      <td>{{ number_format($loan->interest_amount, 2) }}</td>
      <td>{{ $loan->loan_date->format('F d, Y') }}</td>
      <td>{{ $loan->due_date->format('F d, Y') }}</td>
      <td>
        <span class="status-badge
        @switch($loan->status)
          @case('active') bg-blue-200 text-blue-800 @break
          @case('paid') bg-green-200 text-green-800 @break
          @case('overdue') bg-red-200 text-red-800 @break
          @default bg-gray-200 text-gray-800
         @endswitch
        ">
            {{ ucfirst($loan->status) }}
        </span>
      </td>
      <td>{{ number_format($loan->amount + $loan->interest_amount, 2) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<div class="summary">
  <div class="summary-item">
    <strong>Total Loans:</strong>
    <span>{{ $loans->count() }}</span>
  </div>
  <div class="summary-item">
    <strong>Total Amount:</strong>
    <span>{{ number_format($loans->sum('amount'), 2) }}</span>
  </div>
  <div class="summary-item">
    <strong>Total Interest:</strong>
    <span>{{ number_format($loans->sum('interest_amount'), 2) }}</span>
  </div>
  <div class="summary-item">
    <strong>Total Repayment:</strong>
    <span>{{ number_format($loans->sum('amount') + $loans->sum('interest_amount'), 2) }}</span>
  </div>
</div>
</body>
</html>
