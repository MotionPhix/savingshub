<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('groups', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();

      // Group Identification and Basic Info
      $table->string('name');
      $table->string('slug')->unique();
      $table->text('description')->nullable();
      $table->text('mission_statement')->nullable();

      // Contribution Details
      $table->enum('contribution_frequency', [
        'weekly',
        'monthly',
        'quarterly',
        'annually'
      ])->default('monthly');
      $table->decimal('contribution_amount', 12, 2);
      $table->integer('duration_months');
      $table->date('start_date');
      $table->date('end_date')->nullable();

      // Group Status and Visibility
      $table->enum('status', [
        'active',
        'completed',
        'paused',
        'dissolved',
        'pending'
      ])->default('active');
      $table->boolean('is_public')->default(false);
      $table->boolean('allow_member_invites')->default(true);

      // Loan Configuration
      $table->enum('loan_interest_type', [
        'fixed',
        'variable',
        'tiered'
      ])->default('fixed');
      $table->decimal('base_interest_rate', 5, 2)->default(5.00);
      $table->json('interest_tiers')->nullable();
      $table->decimal('max_loan_amount', 10, 2)->nullable();
      $table->integer('loan_duration_months')->default(12);
      $table->boolean('require_group_approval')->default(false);

      // Additional Group Settings
      $table->json('settings')->nullable();
      $table->json('notification_preferences')->nullable();

      // Soft Delete and Timestamps
      $table->softDeletes();
      $table->timestamps();

      // Foreign Key Constraints
      $table->foreignId('created_by')
        ->constrained('users')
        ->cascadeOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('groups');
  }
};
