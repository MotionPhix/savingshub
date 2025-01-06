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
    Schema::create('group_members', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();
      $table->foreignId('group_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      // Membership Details
      $table->enum('role', [
        'admin',
        'member',
        'treasurer',
        'secretary',
        'moderator'
      ])->default('member');

      // Membership Status
      $table->enum('status', [
        'active',
        'pending',
        'invited',
        'suspended',
        'left'
      ])->default('pending');

      // Contribution and Loan Tracking
      $table->decimal('total_contributions', 12, 2)->default(0);
      $table->decimal('total_loans', 12, 2)->default(0);
      $table->integer('contribution_count')->default(0);
      $table->integer('loan_count')->default(0);

      // Permissions and Access
      $table->json('custom_permissions')->nullable();
      $table->timestamp('joined_at')->nullable();
      $table->timestamp('last_activity_at')->nullable();

      // Soft delete and timestamps
      $table->softDeletes();
      $table->timestamps();

      // Unique constraint
      $table->unique(['group_id', 'user_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('group_members');
  }
};
