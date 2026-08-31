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
        // 1. Upgrade reports table columns
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'reportType')) {
                $table->string('reportType', 50)->default('account')->after('reportedId');
            }
            if (!Schema::hasColumn('reports', 'productId')) {
                $table->uuid('productId')->nullable()->after('reportType');
            }
            if (!Schema::hasColumn('reports', 'severity')) {
                $table->string('severity', 20)->default('MEDIUM')->after('evidence');
            }
            if (!Schema::hasColumn('reports', 'investigationResult')) {
                $table->string('investigationResult', 100)->nullable()->after('adminNotes');
            }
            if (!Schema::hasColumn('reports', 'disciplinaryReason')) {
                $table->text('disciplinaryReason')->nullable()->after('investigationResult');
            }
            if (!Schema::hasColumn('reports', 'sellerResponse')) {
                $table->text('sellerResponse')->nullable()->after('disciplinaryReason');
            }
            if (!Schema::hasColumn('reports', 'sellerResponseEvidence')) {
                $table->text('sellerResponseEvidence')->nullable()->after('sellerResponse');
            }
            if (!Schema::hasColumn('reports', 'sellerRespondedAt')) {
                $table->timestamp('sellerRespondedAt')->nullable()->after('sellerResponseEvidence');
            }
            if (!Schema::hasColumn('reports', 'assignedAdminId')) {
                $table->uuid('assignedAdminId')->nullable()->after('sellerRespondedAt');
            }
        });

        // 2. Create report_timeline_events table
        if (!Schema::hasTable('report_timeline_events')) {
            Schema::create('report_timeline_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('report_id')->index();
                $table->uuid('actor_id')->nullable()->index();
                $table->string('actor_role', 50)->default('system'); // system, customer, seller, admin, superadmin
                $table->string('event_type', 50); // report_submitted, received, under_review, severity_set, seller_response, investigation_updated, action_taken, resolved, dismissed, escalated
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['report_id', 'created_at']);
            });

            // Attempt foreign key constraint creation gracefully
            try {
                Schema::table('report_timeline_events', function (Blueprint $table) {
                    $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Graceful fallback for MySQL/MariaDB engines with collation variance
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_timeline_events');

        Schema::table('reports', function (Blueprint $table) {
            $cols = [
                'reportType', 'productId', 'severity', 'investigationResult', 
                'disciplinaryReason', 'sellerResponse', 'sellerResponseEvidence', 
                'sellerRespondedAt', 'assignedAdminId'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
