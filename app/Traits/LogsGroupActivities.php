<?php
namespace App\Traits;

use App\Services\GroupActivityService;

trait LogsGroupActivities
{
  protected static function bootLogsGroupActivities()
  {
    static::created(function ($model) {
      if (method_exists($model, 'group')) {
        $activityService = app(GroupActivityService::class);
        $activityService->log(
          $model->group,
          strtolower(class_basename(static::class)) . '_created',
          auth()->user()
        );
      }
    });

    static::updated(function ($model) {
      if (method_exists($model, 'group')) {
        $activityService = app(GroupActivityService::class);
        $changes = $model->getChanges();

        $activityService->log(
          $model->group,
          strtolower(class_basename(static::class)) . '_updated',
          auth()->user(),
          $changes
        );
      }
    });
  }
}
