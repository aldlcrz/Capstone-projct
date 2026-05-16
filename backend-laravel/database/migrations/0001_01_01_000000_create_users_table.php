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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('username')->unique()->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('customer'); // admin, seller, customer
            $table->boolean('isVerified')->default(false);
            $table->string('profilePhoto')->nullable();
            
            // Seller Specific
            $table->string('indigencyCertificate')->nullable();
            $table->string('validId')->nullable();
            $table->string('mobileNumber')->nullable();
            $table->string('gcashNumber')->nullable();
            $table->string('gcashQrCode')->nullable();
            $table->string('mayaNumber')->nullable();
            $table->string('mayaQrCode')->nullable();
            
            // Social Links (JSON)
            $table->json('socialLinks')->nullable();
            $table->string('facebookLink')->nullable();
            $table->string('instagramLink')->nullable();
            $table->string('tiktokLink')->nullable();
            $table->string('youtubeLink')->nullable();
            
            // Shop / Business Address
            $table->string('shopHouseNo')->nullable();
            $table->string('shopStreet')->nullable();
            $table->string('shopAddress')->nullable();
            $table->string('shopBarangay')->nullable();
            $table->string('shopCity')->nullable();
            $table->string('shopProvince')->nullable();
            $table->string('shopPostalCode')->nullable();
            $table->decimal('shopLatitude', 10, 8)->nullable();
            $table->decimal('shopLongitude', 11, 8)->nullable();
            
            $table->boolean('isAdult')->default(false);
            $table->string('fcmToken')->nullable();
            $table->json('followers')->nullable();
            $table->json('following')->nullable();
            $table->string('status')->default('active'); // active, blocked, frozen
            $table->text('violationReason')->nullable();
            $table->text('rejectionReason')->nullable();
            $table->integer('sessionVersion')->default(1);
            $table->string('googleId')->nullable();
            $table->boolean('hasPasswordSet')->default(true);
            $table->integer('loginAttempts')->default(0);
            $table->timestamp('loginLockedUntil')->nullable();
            $table->text('bio')->nullable();
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            
            // Password Reset
            $table->string('resetPasswordToken')->nullable();
            $table->timestamp('resetPasswordExpires')->nullable();
            
            $table->rememberToken();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
