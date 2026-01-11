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
        Schema::create('invoices', function (Blueprint $table) {
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
                ->foreignId('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number');
            $table->string('status');

            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();

            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');

            $table->text('notes')->nullable();

            $table
                ->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'client_id']);
            $table->index(['organization_id', 'project_id']);
            $table->index(['organization_id', 'due_at']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

