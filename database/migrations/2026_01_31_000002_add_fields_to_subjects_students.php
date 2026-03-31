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
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'section')) {
                $table->string('section')->nullable()->after('name');
            }
            if (!Schema::hasColumn('subjects', 'instructor')) {
                $table->string('instructor')->nullable()->after('section');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'year_level')) {
                $table->string('year_level')->nullable()->after('program');
            }
            if (!Schema::hasColumn('students', 'student_email_verified')) {
                $table->boolean('student_email_verified')->default(false)->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['section', 'instructor']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['year_level', 'student_email_verified']);
        });
    }
};
