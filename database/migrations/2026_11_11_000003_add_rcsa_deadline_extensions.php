<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rcsa_periods', function (Blueprint $table) {
            $table->date('deadline_extended_to')->nullable()->after('ends_on');
        });

        Schema::table('rcsa_submissions', function (Blueprint $table) {
            $table->date('deadline_extended_to')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('rcsa_periods', function (Blueprint $table) {
            $table->dropColumn('deadline_extended_to');
        });

        Schema::table('rcsa_submissions', function (Blueprint $table) {
            $table->dropColumn('deadline_extended_to');
        });
    }
};
