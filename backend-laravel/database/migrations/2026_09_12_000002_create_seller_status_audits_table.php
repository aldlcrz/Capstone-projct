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
        if (!Schema::hasTable('seller_status_audits')) {
            Schema::create('seller_status_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('seller_id')->index();
                $table->uuid('admin_id')->nullable()->index();
                $table->string('previous_status', 50)->nullable();
                $table->string('new_status', 50);
                $table->text('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_status_audits');
    }
};
