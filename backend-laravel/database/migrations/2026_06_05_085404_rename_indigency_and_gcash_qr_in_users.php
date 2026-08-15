<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'residencyCertificate')) {
                $table->string('residencyCertificate')->nullable();
            }
            if (!Schema::hasColumn('users', 'birDocument')) {
                $table->string('birDocument')->nullable();
            }
        });

        // Copy data from indigencyCertificate to residencyCertificate if residencyCertificate is null
        if (Schema::hasColumn('users', 'indigencyCertificate')) {
            DB::table('users')
                ->whereNull('residencyCertificate')
                ->whereNotNull('indigencyCertificate')
                ->update([
                    'residencyCertificate' => DB::raw('indigencyCertificate')
                ]);
        }

        // Copy data from birCertificate to birDocument if birDocument is null
        if (Schema::hasColumn('users', 'birCertificate')) {
            DB::table('users')
                ->whereNull('birDocument')
                ->whereNotNull('birCertificate')
                ->update([
                    'birDocument' => DB::raw('birCertificate')
                ]);
        }

        // Copy data from gcashQrCode to birDocument if birDocument is still null
        if (Schema::hasColumn('users', 'gcashQrCode')) {
            DB::table('users')
                ->whereNull('birDocument')
                ->whereNotNull('gcashQrCode')
                ->update([
                    'birDocument' => DB::raw('gcashQrCode')
                ]);
        }

        // Drop old columns
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'indigencyCertificate')) {
                $table->dropColumn('indigencyCertificate');
            }
            if (Schema::hasColumn('users', 'gcashQrCode')) {
                $table->dropColumn('gcashQrCode');
            }
            if (Schema::hasColumn('users', 'birCertificate')) {
                $table->dropColumn('birCertificate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'indigencyCertificate')) {
                $table->string('indigencyCertificate')->nullable();
            }
            if (!Schema::hasColumn('users', 'gcashQrCode')) {
                $table->string('gcashQrCode')->nullable();
            }
            if (!Schema::hasColumn('users', 'birCertificate')) {
                $table->string('birCertificate')->nullable();
            }
        });

        // Copy data back
        if (Schema::hasColumn('users', 'residencyCertificate')) {
            DB::table('users')
                ->whereNull('indigencyCertificate')
                ->whereNotNull('residencyCertificate')
                ->update([
                    'indigencyCertificate' => DB::raw('residencyCertificate')
                ]);
        }

        if (Schema::hasColumn('users', 'birDocument')) {
            DB::table('users')
                ->whereNull('birCertificate')
                ->whereNotNull('birDocument')
                ->update([
                    'birCertificate' => DB::raw('birDocument')
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'residencyCertificate')) {
                $table->dropColumn('residencyCertificate');
            }
            if (Schema::hasColumn('users', 'birDocument')) {
                $table->dropColumn('birDocument');
            }
        });
    }
};
