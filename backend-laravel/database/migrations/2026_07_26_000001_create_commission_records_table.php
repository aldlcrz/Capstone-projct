<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->uuid('sellerId')->collation('utf8mb4_bin');
            } else {
                $table->uuid('sellerId');
            }
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->string('period'); // e.g. "2025-07"
            $table->decimal('totalSales', 12, 2)->default(0);
            $table->decimal('commissionRate', 5, 2)->default(5.00); // 5%
            $table->decimal('commissionAmount', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'paid', 'waived'])->default('unpaid');
            $table->timestamp('dueDate')->nullable(); // end of month + grace period
            $table->timestamp('paidAt')->nullable();
            $table->boolean('warningNotified')->default(false); // 1-week warning sent?
            $table->boolean('freezeNotified')->default(false);  // frozen notification sent?
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sellerId', 'period']); // one record per seller per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_records');
    }
};
