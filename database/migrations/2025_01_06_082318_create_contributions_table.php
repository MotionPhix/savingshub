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
    Schema::create('contributions', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();

      // Foreign keys using UUID
      $table->foreignId('group_member_id')
        ->constrained('group_members')
        ->cascadeOnDelete();

      $table->foreignId('group_id')
        ->constrained('groups')
        ->cascadeOnDelete();

      $table->foreignId('user_id')
        ->constrained('users')
        ->cascadeOnDelete();

      // Contribution Details
      $table->decimal('amount', 12, 2);
      $table->date('contribution_date');

      // Contribution Status and Type
      $table->enum('status', [
        'pending',
        'paid',
        'overdue',
        'partial',
        'failed'
      ])->default('pending');

      $table->enum('type', [
        'regular',
        'extra',
        'makeup',
        'penalty'
      ])->default('regular');

      // Payment Method and Verification
      $table->string('payment_method')->nullable();
      $table->string('transaction_reference')->nullable();
      $table->boolean('is_verified')->default(false);

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
    Schema::dropIfExists('contributions');
  }
};
