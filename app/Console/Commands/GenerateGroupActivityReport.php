<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use App\Services\GroupActivityService;
use App\Models\Group;

class GenerateGroupActivityReport extends Command
{
  protected $signature = 'group:activity-report
        {group_id : The ID of the group}
        {--start= : Start date for the report}
        {--end= : End date for the report}';

  protected $description = 'Generate an activity report for a specific group';

  protected $activityService;

  public function __construct(GroupActivityService $activityService)
  {
    parent::__construct();
    $this->activityService = $activityService;
  }

  public function handle()
  {
    $groupId = $this->argument('group_id');
    $group = Group::findOrFail($groupId);

    $startDate = $this->option('start')
      ? \Carbon\Carbon::parse($this->option('start'))
      : now()->subMonth();

    $endDate = $this->option('end')
      ? \Carbon\Carbon::parse($this->option('end'))
      : now();

    $report = $this->activityService->generateActivityReport(
      $group,
      $startDate,
      $endDate
    );

    $this->table(
      ['Metric', 'Value'],
      [
        ['Total Activities', $report['total_activities']],
        ...collect($report['activity_breakdown'])
          ->map(fn($count, $type) => [$type, $count])
          ->toArray()
      ]
    );

    return 0;
  }

  public function generateReportFile(Group $group, $startDate, $endDate)
  {
    $report = $this->activityService->generateActivityReport(
      $group,
      $startDate,
      $endDate
    );

    // Generate PDF or CSV report
    $filename = "group_{$group->id}_activity_report_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}.pdf";
    $path = storage_path("app/group_reports/{$filename}");

    // Use a PDF library like FPDF or Laravel's PDF package
    $pdf = PDF::loadView('reports.group-activity', [
      'group' => $group,
      'report' => $report,
      'startDate' => $startDate,
      'endDate' => $endDate
    ]);

    $pdf->save($path);

    return $path;
  }
}
