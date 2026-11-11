<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rcsa_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('label', 40);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        Schema::create('rcsa_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rcsa_period_id')->constrained('rcsa_periods')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // pending | submitted
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_by')->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('yes_count')->default(0);
            $table->unsignedInteger('no_count')->default(0);
            $table->unsignedInteger('na_count')->default(0);
            $table->unsignedInteger('ne_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['rcsa_period_id', 'branch_id']);
            $table->index(['rcsa_period_id', 'status']);
        });

        Schema::create('rcsa_submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rcsa_submission_id')->constrained('rcsa_submissions')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->text('item');
            $table->string('status', 10)->default('N/E');
            $table->text('remarks')->nullable();
            $table->text('action_plan')->nullable();
            $table->date('target_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['rcsa_submission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rcsa_submission_items');
        Schema::dropIfExists('rcsa_submissions');
        Schema::dropIfExists('rcsa_periods');
    }
};
