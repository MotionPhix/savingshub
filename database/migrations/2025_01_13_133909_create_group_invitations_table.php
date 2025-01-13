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
    Schema::create('group_invitations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('group_id')->constrained()->onDelete('cascade');
      $table->string('email');
      $table->enum('role', ['member', 'treasurer', 'secretary'])->default('member');
      $table->string('token')->unique();
      $table->timestamp('expires_at');
      $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
      $table->timestamp('accepted_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('group_invitations');
  }
};
