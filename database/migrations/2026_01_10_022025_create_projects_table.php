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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status');
            $table->string('priority');
            $table->date('due_date')->nullable();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'client_id']);
            $table->index(['organization_id', 'due_date']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
