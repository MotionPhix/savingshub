<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDocument extends Model
{
  protected $fillable = [
    'loan_id',
    'file_path',
    'file_name',
    'mime_type',
    'size'
  ];

  public function loan()
  {
    return $this->belongsTo(Loan::class);
  }

  public function getDownloadUrlAttribute()
  {
    return route('loan.download-document', $this->id);
  }
}
