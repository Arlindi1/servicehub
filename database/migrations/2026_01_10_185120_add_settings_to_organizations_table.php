<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('brand_color')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('brand_color');
            $table->string('invoice_prefix')->nullable()->default('INV')->after('logo_path');
            $table->unsignedInteger('invoice_due_days_default')->default(14)->after('invoice_prefix');
            $table->string('billing_email')->nullable()->after('invoice_due_days_default');
        });

        DB::table('organizations')->whereNull('invoice_prefix')->update(['invoice_prefix' => 'INV']);
        DB::table('organizations')->whereNull('invoice_due_days_default')->update(['invoice_due_days_default' => 14]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'brand_color',
                'logo_path',
                'invoice_prefix',
                'invoice_due_days_default',
                'billing_email',
            ]);
        });
    }
};
