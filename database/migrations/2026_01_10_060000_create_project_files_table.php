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
        Schema::create('project_files', function (Blueprint $table) {
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
                ->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('uploader_type', 20);
            $table->string('file_type', 30);

            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size');

            $table->index(['organization_id', 'project_id']);
            $table->index(['organization_id', 'file_type']);
            $table->index(['organization_id', 'uploaded_by_user_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};

