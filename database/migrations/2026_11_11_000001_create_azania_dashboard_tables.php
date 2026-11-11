<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('module', 30)->default('general');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['module', 'name']);
        });

        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dashboard_options', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50);
            $table->string('option_value', 100);
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group_key', 'option_value']);
        });

        Schema::create('maintenance_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('priority', 30)->default('Medium');
            $table->foreignId('officer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('target_date')->nullable();
            $table->string('status', 40)->default('Open');
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });

        Schema::create('licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('authority')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 40)->default('Valid');
            $table->timestamps();
            $table->index(['branch_id', 'expiry_date']);
        });

        Schema::create('rcsa_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->text('item');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rcsa_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('rcsa_template_id')->constrained('rcsa_templates')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 10)->default('N/E');
            $table->text('remarks')->nullable();
            $table->text('action_plan')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'rcsa_template_id']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rcsa_assessments');
        Schema::dropIfExists('rcsa_templates');
        Schema::dropIfExists('licences');
        Schema::dropIfExists('maintenance_issues');
        Schema::dropIfExists('dashboard_options');
        Schema::dropIfExists('officers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('branches');
    }
};
