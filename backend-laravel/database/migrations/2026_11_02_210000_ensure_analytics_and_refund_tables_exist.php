<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure snake_case analytics and refund tables exist on MySQL environments
     * that were originally initialized from camelCase Prisma tables.
     */
    public function up(): void
    {
        if (!Schema::hasTable('product_views')) {
            Schema::create('product_views', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id')->index();
                $table->uuid('seller_id')->index();
                $table->uuid('customer_id')->nullable()->index();
                $table->string('visitor_session_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });

            if (Schema::hasTable('productviews')) {
                try {
                    $rows = DB::table('productviews')->get();
                    foreach ($rows as $r) {
                        DB::table('product_views')->insert([
                            'product_id'         => $r->productId ?? $r->product_id ?? '',
                            'seller_id'          => $r->sellerId ?? $r->seller_id ?? '',
                            'customer_id'        => $r->customerId ?? $r->customer_id ?? null,
                            'visitor_session_id' => $r->visitorSessionId ?? $r->visitor_session_id ?? null,
                            'ip_address'         => $r->ipAddress ?? $r->ip_address ?? null,
                            'created_at'         => $r->createdAt ?? $r->created_at ?? now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Safe fallback if legacy table is empty or incompatible
                }
            }
        }

        if (!Schema::hasTable('seller_funnel_events')) {
            Schema::create('seller_funnel_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('seller_id')->index();
                $table->uuid('product_id')->index();
                $table->uuid('customer_id')->nullable()->index();
                $table->string('visitor_session_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('event_type')->default('add_to_cart');
                $table->timestamp('created_at')->useCurrent();
            });

            if (Schema::hasTable('sellerfunnelevents')) {
                try {
                    $rows = DB::table('sellerfunnelevents')->get();
                    foreach ($rows as $r) {
                        DB::table('seller_funnel_events')->insert([
                            'seller_id'          => $r->sellerId ?? $r->seller_id ?? '',
                            'product_id'         => $r->productId ?? $r->product_id ?? '',
                            'customer_id'        => $r->customerId ?? $r->customer_id ?? null,
                            'visitor_session_id' => $r->visitorSessionId ?? $r->visitor_session_id ?? null,
                            'ip_address'         => $r->ipAddress ?? $r->ip_address ?? null,
                            'event_type'         => $r->eventType ?? $r->event_type ?? 'add_to_cart',
                            'created_at'         => $r->createdAt ?? $r->created_at ?? now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Safe fallback
                }
            }
        }

        if (!Schema::hasTable('refund_requests')) {
            Schema::create('refund_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('order_id')->index();
                $table->uuid('order_item_id')->index();
                $table->uuid('customer_id')->index();
                $table->uuid('seller_id')->index();
                $table->string('reason')->default('Other');
                $table->text('message')->nullable();
                $table->string('video_proof')->nullable();
                $table->string('status')->default('Pending');
                $table->text('seller_comment')->nullable();
                $table->timestamps();
            });

            if (Schema::hasTable('refundrequests')) {
                try {
                    $rows = DB::table('refundrequests')->get();
                    foreach ($rows as $r) {
                        DB::table('refund_requests')->insert([
                            'order_id'       => $r->orderId ?? $r->order_id ?? '',
                            'order_item_id'  => $r->orderItemId ?? $r->order_item_id ?? '',
                            'customer_id'    => $r->customerId ?? $r->customer_id ?? '',
                            'seller_id'      => $r->sellerId ?? $r->seller_id ?? '',
                            'reason'         => $r->reason ?? 'Other',
                            'message'        => $r->message ?? null,
                            'video_proof'    => $r->videoProof ?? $r->video_proof ?? '',
                            'status'         => $r->status ?? 'Pending',
                            'seller_comment' => $r->sellerComment ?? $r->seller_comment ?? null,
                            'created_at'     => $r->createdAt ?? $r->created_at ?? now(),
                            'updated_at'     => $r->updatedAt ?? $r->updated_at ?? now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Safe fallback
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive
    }
};

