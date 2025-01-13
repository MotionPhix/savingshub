<?php

namespace App\Models;

use App\Traits\BootUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupActivity extends Model
{
  use BootUuid;

  protected $fillable = [
    'group_id',
    'user_id',
    'type',
    'description',
    'changes',
    'metadata'
  ];

  protected $casts = [
    'changes' => 'array',
    'metadata' => 'array'
  ];

  public function group(): BelongsTo
  {
    return $this->belongsTo(Group::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
