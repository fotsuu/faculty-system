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
            $table->string('raw_grade')->nullable()->after('notes');
            $table->double('numeric_grade', 8, 4)->nullable()->after('raw_grade');
            $table->double('grade_point', 8, 4)->nullable()->after('numeric_grade');
            $table->json('scores')->nullable()->after('grade_point');
            $table->integer('row_index')->nullable()->after('scores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['raw_grade', 'numeric_grade', 'grade_point', 'scores', 'row_index']);
        });
    }
};
