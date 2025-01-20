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
    Schema::table('groups', function (Blueprint $table) {
      $table->decimal('allowed_partial_percentage')->default(0.5);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->dropColumn('allowed_partial_percentage');
    });
  }
};
