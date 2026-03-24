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
        Schema::table('records', function (Blueprint $table) {
            if (!Schema::hasColumn('records', 'submission_status')) {
                $table->enum('submission_status', ['pending', 'approved', 'rejected'])->default('pending')->after('row_index');
            }
            if (!Schema::hasColumn('records', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('submission_status');
            }
            if (!Schema::hasColumn('records', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('records', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['submission_status', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
