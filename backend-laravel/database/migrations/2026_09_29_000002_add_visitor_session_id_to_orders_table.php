<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'visitorSessionId')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('visitorSessionId')->nullable()->after('shippingAddress');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'visitorSessionId')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('visitorSessionId');
            });
        }
    }
};
