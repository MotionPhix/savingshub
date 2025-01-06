<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contributions Report</title>
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
    .contributions-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .contributions-table thead {
      background-color: #3498db;
      color: white;
    }
    .contributions-table th,
    .contributions-table td {
      border: 1px solid #ddd;
      padding: 12px;
      text-align: left;
    }
    .contributions-table tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    .contributions-table tr:hover {
      background-color: #e6f2ff;
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
  </style>
</head>
<body>
<div class="report-header">
  <div class="report-title">Contributions Report</div>
  <div class="report-metadata">
    <p>Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
    <p>Group: {{ $contributions->first()->groupMember->group->name ?? 'N/A' }}</p>
  </div>
</div>

<table class="contributions-table">
  <thead>
  <tr>
    <th>Contribution ID</th>
    <th>Amount</th>
    <th>Date</th>
    <th>Status</th>
    <th>Payment Method</th>
  </tr>
  </thead>
  <tbody>
  @foreach($contributions as $contribution)
  <tr>
    <td>{{ $contribution->id }}</td>
    <td>{{ number_format($contribution->amount, 2) }}</td>
    <td>{{ $contribution->contribution_date->format('F d, Y') }}</td>
    <td>
      <span class="
          @switch($contribution->status)
              @case('paid') text-green-600 @break
              @case('pending') text-yellow-600 @break
              @case('overdue') text-red-600 @break
              @default text-gray-600 @endswitch
      ">
          {{ ucfirst($contribution->status) }}
      </span>
    </td>
    <td>{{ $contribution->payment_method ?? 'Not Specified' }}</td>
  </tr>
  @endforeach
  </tbody>
</table>

<div class="summary">
  <div class="summary-item">
    <strong>Total Contributions:</strong>
    <span>{{ $contributions->count() }}</span>
  </div>
  <div class="summary-item">
    <strong>Total Amount:</strong>
    <span>{{ number_format($contributions->sum('amount'), 2) }}</span>
  </div>
  <div class="summary-item">
    <strong>Average Contribution:</strong>
    <span>{{ number_format($contributions->avg('amount'), 2) }}</span>
  </div>
</div>
</body>
</html>
