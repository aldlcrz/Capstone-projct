<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisputeReturnRequestService
{
    /**
     * Customer disputes a seller rejection.
     */
    public function customerOpenDispute(
        ReturnRequest $returnRequest,
        User $customer,
        string $disputeReason,
        array $files = []
    ): ReturnRequest {
        if ($returnRequest->customer_id !== $customer->id && $returnRequest->order->customerId !== $customer->id) {
            throw ValidationException::withMessages([
                'customer' => ['Unauthorized. This is not your return request.'],
            ]);
        }

        if ($returnRequest->return_status !== 'rejected') {
            throw ValidationException::withMessages([
                'dispute' => ['Only rejected return requests can be disputed.'],
            ]);
        }

        return DB::transaction(function () use ($returnRequest, $customer, $disputeReason, $files) {
            $returnRequest->update([
                'dispute_status' => 'opened',
                'return_status'  => 'disputed',
                'status'         => 'Pending',
                'admin_notes'    => 'Customer Dispute: ' . $disputeReason,
            ]);

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('returns/disputes', 'public');
                    ReturnRefundEvidence::create([
                        'return_request_id' => $returnRequest->id,
                        'uploaded_by'       => $customer->id,
                        'type'              => 'dispute_proof',
                        'storage_path'      => $path,
                        'mime_type'         => $file->getClientMimeType(),
                        'file_size'         => $file->getSize(),
                        'checksum'          => md5_file($file->getRealPath()),
                    ]);
                }
            }

            // Notify Admin
            $adminUser = User::whereIn('role', ['admin', 'superadmin'])->first();
            if ($adminUser) {
                Notification::create([
                    'userId'     => $adminUser->id,
                    'title'      => 'Return Case Disputed',
                    'message'    => "Customer escalated a rejected return for Order #LB-OR-" . strtoupper(substr($returnRequest->orderId, -8)),
                    'targetRole' => 'admin',
                ]);
            }

            // Notify Seller
            Notification::create([
                'userId'     => $returnRequest->seller_id ?: $returnRequest->order->sellerId,
                'title'      => 'Return Decision Disputed',
                'message'    => "The customer has escalated your return rejection to platform administrators.",
                'targetRole' => 'seller',
            ]);

            return $returnRequest->fresh(['evidences', 'order']);
        });
    }

    /**
     * Admin resolves a disputed return request.
     */
    public function adminResolveDispute(
        ReturnRequest $returnRequest,
        User $admin,
        string $decision, // 'approve_return' | 'uphold_rejection'
        ?string $notes = null
    ): ReturnRequest {
        if (!in_array($admin->role, ['admin', 'superadmin'], true)) {
            throw ValidationException::withMessages([
                'admin' => ['Unauthorized.'],
            ]);
        }

        return DB::transaction(function () use ($returnRequest, $admin, $decision, $notes) {
            $isApproved = $decision === 'approve_return';

            $returnRequest->update([
                'dispute_status' => 'resolved',
                'return_status'  => $isApproved ? 'admin_review' : 'rejected',
                'status'         => $isApproved ? 'Approved' : 'Rejected',
                'admin_decision' => $isApproved ? 'override_approved' : 'rejection_upheld',
                'admin_notes'    => $notes,
                'resolved_by'    => $admin->id,
            ]);

            // Notify Customer
            Notification::send(
                $returnRequest->customer_id ?: $returnRequest->order->customerId,
                $isApproved ? 'Dispute Resolved in Your Favor' : 'Dispute Decision Upheld',
                $isApproved 
                    ? "Platform Admin approved your return claim following mediation review."
                    : "Platform Admin reviewed the dispute and upheld the seller decision: " . ($notes ?: 'Claim ineligible.'),
                'order',
                route('orders.show', $returnRequest->orderId),
                'customer'
            );

            return $returnRequest->fresh(['evidences', 'order']);
        });
    }
}
