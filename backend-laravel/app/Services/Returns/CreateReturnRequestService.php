<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\Order;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateReturnRequestService
{
    public function __construct(
        protected EvaluateReturnEligibilityService $eligibilityService
    ) {}

    /**
     * Create a new Return/Refund request.
     */
    public function create(User $customer, array $data, array $uploadedFiles = []): ReturnRequest
    {
        $order = Order::findOrFail($data['orderId']);
        $orderItemId = $data['order_item_id'] ?? null;

        $evaluation = $this->eligibilityService->evaluateEligibility($order, $customer, $orderItemId);
        if (!$evaluation['eligible']) {
            throw ValidationException::withMessages([
                'orderId' => [$evaluation['reason']],
            ]);
        }

        $resolutionType = $data['resolution_type'] ?? 'refund';
        $isOnlinePayment = in_array(strtolower($order->paymentMethod ?? ''), ['gcash', 'maya'], true);

        return DB::transaction(function () use ($order, $customer, $data, $uploadedFiles, $orderItemId, $evaluation, $resolutionType, $isOnlinePayment) {
            $returnRequest = ReturnRequest::create([
                'orderId'                => $order->id,
                'customer_id'            => $customer->id,
                'seller_id'              => $order->sellerId,
                'order_item_id'          => $orderItemId,
                'reason'                 => $data['reason'],
                'adminComment'           => $data['message'] ?? null,
                'status'                 => 'Pending',
                'return_status'          => 'submitted',
                'physical_return_status' => 'not_required',
                'refund_status'          => $isOnlinePayment ? 'pending' : 'not_applicable',
                'dispute_status'         => 'none',
                'resolution_type'        => $resolutionType,
                'requested_amount'       => $evaluation['max_allowed_amount'],
                'approved_amount'        => 0.00,
            ]);

            $storedPaths = [];
            foreach ($uploadedFiles as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('returns', 'public');
                    $mime = $file->getClientMimeType();
                    $size = $file->getSize();
                    $type = str_contains($mime, 'video') ? 'video' : 'photo';

                    ReturnRefundEvidence::create([
                        'return_request_id' => $returnRequest->id,
                        'uploaded_by'       => $customer->id,
                        'type'              => $type,
                        'storage_path'      => $path,
                        'mime_type'         => $mime,
                        'file_size'         => $size,
                        'checksum'          => md5_file($file->getRealPath()),
                    ]);
                    $storedPaths[] = '/storage/' . $path;
                }
            }

            if (!empty($storedPaths)) {
                $returnRequest->update([
                    'proofImages' => json_encode($storedPaths),
                ]);
            }

            // In-app Notification to Seller
            Notification::create([
                'userId'     => $order->sellerId,
                'title'      => 'New Return/Refund Request',
                'message'    => "A customer requested a return/refund for Order #LB-OR-" . strtoupper(substr($order->id, -8)),
                'targetRole' => 'seller',
            ]);

            // Email Notification to Seller
            try {
                $sellerUser = User::find($order->sellerId);
                if ($sellerUser && $sellerUser->email) {
                    $mailable = new \App\Mail\ReturnRefundRequestMail($sellerUser->name, $order->id, $data['reason'], ucfirst($resolutionType));
                    \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $mailable, 'return_refund_request', $sellerUser->id, 'Order', $order->id);
                }
            } catch (\Throwable $e) {
                Log::error('Failed sending return notification email', ['error' => $e->getMessage()]);
            }

            return $returnRequest->load(['evidences', 'order']);
        });
    }
}
