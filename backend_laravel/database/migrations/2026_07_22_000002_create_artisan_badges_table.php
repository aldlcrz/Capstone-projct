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
        Schema::dropIfExists('artisan_badges');
        Schema::create('artisan_badges', function (Blueprint $table) {
            $table->id();
            $table->uuid('seller_id');
            $table->string('badge_type'); // e.g. certified_lumban_artisan, hand_embroidered_master, heritage_craftsperson
            $table->uuid('issued_by')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['seller_id', 'badge_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artisan_badges');
    }
};
