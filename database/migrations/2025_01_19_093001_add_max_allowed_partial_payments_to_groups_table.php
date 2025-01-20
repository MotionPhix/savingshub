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
      $table->tinyInteger('max_allowed_partial_contributions')->default(2);
      $table->tinyInteger('allow_contributions_until')->default(5);
      $table->decimal('penalty_fee_percentage', 4)->default(0.05);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->dropColumn('max_allowed_partial_contributions');
      $table->dropColumn('allow_contributions_until');
      $table->dropColumn('penalty_fee_percentage');
    });
  }
};
