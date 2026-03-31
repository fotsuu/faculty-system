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
            $table->string('midterm_total')->nullable()->after('row_index');
            $table->string('final_term_total')->nullable()->after('midterm_total');
            $table->string('total_all')->nullable()->after('final_term_total');
        });
        
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'year_level')) {
                $table->string('year_level')->nullable()->after('program');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['midterm_total', 'final_term_total', 'total_all']);
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['year_level']);
        });
    }
};
