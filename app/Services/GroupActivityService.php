<?php
namespace App\Services;

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GroupActivityService
{
  public function log(
    Group $group,
    string $type,
    ?User $user = null,
    ?array $changes = null,
    ?array $metadata = null
  ): GroupActivity {
    try {
      // Enrich metadata with additional context
      $enrichedMetadata = $this->enrichMetadata($metadata, $user);

      $activity = GroupActivity::create([
        'uuid' => Str::uuid(),
        'group_id' => $group->id,
        'user_id' => $user?->id,
        'type' => $type,
        'description' => $this->generateDescription($type, $user, $enrichedMetadata),
        'changes' => $changes,
        'metadata' => $enrichedMetadata
      ]);

      // Additional logging for critical activities
      $this->logCriticalActivities($type, $group, $user, $enrichedMetadata);

      return $activity;
    } catch (\Exception $e) {
      // Fallback logging
      Log::error('Group activity logging failed', [
        'type' => $type,
        'group_id' => $group->id,
        'user_id' => $user?->id,
        'error' => $e->getMessage()
      ]);

      throw $e;
    }
  }

  private function enrichMetadata(?array $metadata, ?User $user): array
  {
    $enrichedMetadata = $metadata ?? [];

    // Add additional context
    $enrichedMetadata['logged_at'] = now()->toIso8601String();
    $enrichedMetadata['ip_address'] = request()->ip();
    $enrichedMetadata['user_agent'] = request()->userAgent();

    // Add user details if available
    if ($user) {
      $enrichedMetadata['user_details'] = [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email
      ];
    }

    return $enrichedMetadata;
  }

  private function logCriticalActivities(
    string $type,
    Group $group,
    ?User $user,
    array $metadata
  ) {
    $criticalTypes = [
      'group_settings_updated',
      'member_role_changed',
      'group_archived',
      'group_created',
      'member_removed'
    ];

    if (in_array($type, $criticalTypes)) {
      Log::channel('critical_group_activities')->info(
        "Critical Group Activity: {$type}",
        [
          'group_id' => $group->id,
          'group_name' => $group->name,
          'user_id' => $user?->id,
          'user_name' => $user?->name,
          'metadata' => $metadata
        ]
      );
    }
  }

  private function generateDescription(
    string $type,
    ?User $user = null,
    ?array $metadata = null
  ): string {
    $userName = $user ? $user->name : 'System';

    return match($type) {
      'member_joined' => "{$userName} joined the group",
      'member_left' => "{$userName} left the group",
      'member_invited' => "{$userName} was invited to the group",
      'contribution_made' => "{$userName} made a contribution of " .
        ($metadata['amount'] ?? 'N/A'),
      'partial_contribution_made' => "{$userName} made a a partial contribution of " .
        ($metadata['amount'] ?? 'N/A'),
      'loan_requested' => "{$userName} requested a loan of " .
        ($metadata['amount'] ?? 'N/A'),
      'loan_approved' => "Loan for {$userName} was approved",
      'loan_rejected' => "Loan for {$userName} was rejected",
      'group_settings_updated' => "Group settings were updated",
      'group_created' => "Group was created",
      'group_archived' => "Group was archived",
      'invitation_expired' => "Invitation expired for {$userName}",
      'invitation_accepted' => "{$userName} accepted group invitation",
      default => "Activity occurred"
    };
  }

  // Retrieve recent group activities with advanced filtering
  public function getRecentActivities(
    Group $group,
    int $limit = 10,
    array $filterTypes = []
  ) {
    $query = $group->activities()
      ->with(['user:id,name'])
      ->latest();

    // Apply type filtering if specified
    if (!empty($filterTypes)) {
      $query->whereIn('type', $filterTypes);
    }

    return $query->limit($limit)
      ->get()
      ->map(function ($activity) {
        return [
          'id' => $activity->id,
          'type' => $activity->type,
          'description' => $activity->description,
          'user' => [
            'name' => $activity->user?->name,
            'avatar' => $activity->user?->avatar
          ],
          'created_at' => $activity->created_at,
          'metadata' => $activity->metadata
        ];
      });
  }

  // Generate activity report
  public function generateActivityReport(
    Group $group,
    \Carbon\Carbon $startDate,
    \Carbon\Carbon $endDate
  ) {
    $activities = $group->activities()
      ->whereBetween('created_at', [$startDate, $endDate])
      ->groupBy('type')
      ->selectRaw('type, COUNT(*) as count')
      ->get();

    return [
      'total_activities' => $activities->sum('count'),
      'activity_breakdown' => $activities->pluck('count', 'type')->toArray(),
      'start_date' => $startDate,
      'end_date' => $endDate
    ];
  }
}
