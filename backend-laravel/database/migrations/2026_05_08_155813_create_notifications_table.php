<?php
/* Notifications Migration */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('system');
            $table->string('link')->nullable();
            $table->boolean('isRead')->default(false);
            $table->string('targetRole')->default('customer');
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['userId', 'createdAt']);
            $table->index(['userId', 'isRead']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
