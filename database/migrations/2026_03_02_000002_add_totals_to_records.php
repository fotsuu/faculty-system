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
            if (!Schema::hasColumn('records', 'laboratory_total')) {
                $table->string('laboratory_total')->nullable()->after('total_all');
            }
            if (!Schema::hasColumn('records', 'non_laboratory_total')) {
                $table->string('non_laboratory_total')->nullable()->after('laboratory_total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['laboratory_total', 'non_laboratory_total']);
        });
    }
};
