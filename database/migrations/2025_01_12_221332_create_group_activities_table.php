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
    Schema::create('group_activities', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->index();

      $table->foreignId('group_id')
        ->constrained()
        ->cascadeOnDelete();

      $table->foreignId('user_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

      $table->enum('type', [
        'member_joined',
        'member_left',
        'member_invited',
        'contribution_made',
        'loan_requested',
        'loan_approved',
        'loan_rejected',
        'group_settings_updated',
        'group_created',
        'group_archived'
      ]);

      $table->string('description')->nullable();
      $table->json('changes')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('group_activities');
  }
};
