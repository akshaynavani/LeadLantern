<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('default_priority')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('interaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('channel')->default('email');
            $table->string('status')->default('new'); // new, routed, needs_review, in_progress, resolved
            $table->string('category')->nullable();
            $table->string('priority')->nullable(); // low, medium, high, urgent
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('routed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('routing_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('source'); // llm, fallback, manual
            $table->string('category');
            $table->string('priority');
            $table->string('sentiment')->nullable();
            $table->decimal('confidence', 4, 3)->default(0);
            $table->text('rationale')->nullable();
            $table->json('suggested_actions')->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_decisions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('routing_rules');
    }
};
