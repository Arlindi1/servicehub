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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('author_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('author_type', 20);
            $table->text('body');

            $table->index(['organization_id', 'project_id']);
            $table->index(['organization_id', 'author_user_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

