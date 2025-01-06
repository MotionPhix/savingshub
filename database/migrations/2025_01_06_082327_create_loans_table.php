<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('loans', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();
      $table->foreignId('group_member_id')
        ->constrained('group_members')
        ->cascadeOnDelete();

      $table->foreignId('group_id')
        ->constrained('groups')
        ->cascadeOnDelete();

      $table->foreignId('user_id')
        ->constrained('users')
        ->cascadeOnDelete();

      // Loan Financial Details
      $table->decimal('principal_amount', 12, 2);
      $table->decimal('interest_amount', 12, 2);
      $table->decimal('total_amount', 12, 2);
      $table->decimal('interest_rate', 5, 2);

      // Loan Timing and Repayment
      $table->date('loan_date');
      $table->date('due_date');
      $table->date('first_payment_date')->nullable();
      $table->integer('duration_months');

      // Loan Status and Tracking
      $table->enum('status', [
        'pending',
        'active',
        'paid',
        'overdue',
        'defaulted',
        'rejected'
      ])->default('pending');

      $table->decimal('total_paid_amount', 12, 2)->default(0);
      $table->integer('missed_payments')->default(0);

      // Approval and Processing
      $table->foreignId('approved_by')
        ->constrained('users')
        ->nullOnDelete();

      $table->timestamp('approved_at')->nullable();
      $table->text('approval_notes')->nullable();

      // Repayment Schedule
      $table->decimal('monthly_payment', 10, 2)->nullable();
      $table->json('payment_schedule')->nullable();

      // Additional Metadata
      $table->json('metadata')->nullable();

      // Timestamps and Soft Delete
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('loans');
  }
};
