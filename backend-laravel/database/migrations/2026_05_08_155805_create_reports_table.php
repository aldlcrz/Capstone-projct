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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('reported_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['CustomerReportingSeller', 'SellerReportingCustomer']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason');
            $table->text('description');
            $table->text('evidence')->nullable();
            $table->enum('status', ['Pending', 'In Review', 'Resolved', 'Dismissed'])->default('Pending');
            $table->text('admin_notes')->nullable();
            $table->enum('action_taken', ['None', 'Warning', 'Restricted', 'Suspended'])->nullable();
            $table->timestamps();
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
