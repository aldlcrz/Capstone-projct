<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reporterId')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('reportedId')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['CustomerReportingSeller', 'SellerReportingCustomer']);
            $table->uuid('referenceId')->nullable();
            $table->string('reason');
            $table->text('description');
            $table->text('evidence')->nullable();
            $table->enum('status', ['Pending', 'In Review', 'Resolved', 'Dismissed'])->default('Pending');
            $table->text('adminNotes')->nullable();
            $table->enum('actionTaken', ['None', 'Warning', 'Restricted', 'Suspended'])->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
