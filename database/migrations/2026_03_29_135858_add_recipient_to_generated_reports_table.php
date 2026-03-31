<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('generated_reports', function (Blueprint $table) {
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_reports', function (Blueprint $table) {
            $table->dropForeign(['recipient_id']);
            $table->dropColumn(['recipient_id', 'submitted_at']);
        });
    }
};
