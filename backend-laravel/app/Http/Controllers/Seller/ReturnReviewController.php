<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\Returns\RecordCashRefundService;
use App\Services\Returns\SellerReturnReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnReviewController extends Controller
{
    public function __construct(
        protected SellerReturnReviewService $reviewService,
        protected RecordCashRefundService $cashRefundService
    ) {}

    /**
     * List returns for the authenticated seller.
     */
    public function index()
    {
        $seller = Auth::user();
        $returns = ReturnRequest::where('seller_id', $seller->id)
            ->orWhereHas('order', fn($q) => $q->where('sellerId', $seller->id))
            ->with(['order', 'evidences', 'customer'])
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($returns);
    }

    /**
     * Seller product assessment (accept / reject).
     */
    public function review(Request $request, ReturnRequest $returnRequest)
    {
        $request->validate([
            'assessment'               => 'required|in:accepted,rejected',
            'notes'                    => 'nullable|string|max:1000',
            'requires_physical_return' => 'nullable|boolean',
        ]);

        $seller = Auth::user();
        $assessment = $request->input('assessment');
        $notes = $request->input('notes');
        $requiresPhysical = (bool) $request->input('requires_physical_return', true);

        $updated = $this->reviewService->reviewAssessment($returnRequest, $seller, $assessment, $notes, $requiresPhysical);

        return response()->json([
            'success'       => true,
            'message'       => 'Assessment recorded successfully.',
            'returnRequest' => $updated,
        ]);
    }

    /**
     * Seller confirms receipt of physically returned item.
     */
    public function receive(Request $request, ReturnRequest $returnRequest)
    {
        $seller = Auth::user();
        $inspectionNotes = $request->input('notes');

        $updated = $this->reviewService->confirmPhysicalReceived($returnRequest, $seller, $inspectionNotes);

        return response()->json([
            'success'       => true,
            'message'       => 'Physical return receipt confirmed.',
            'returnRequest' => $updated,
        ]);
    }

    /**
     * Seller records cash refund / exchange resolution (Store Pickup & COD).
     */
    public function cashRefund(Request $request, ReturnRequest $returnRequest)
    {
        $request->validate([
            'refund_amount'   => 'required|numeric|min:0',
            'resolution_type' => 'required|in:refund,exchange,replacement,alteration',
            'notes'           => 'nullable|string|max:1000',
            'refund_date'     => 'nullable|date',
            'transfer_proof'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $seller = Auth::user();
        $amount = (float) $request->input('refund_amount');
        $resolution = $request->input('resolution_type');
        $notes = $request->input('notes');
        $proof = $request->file('transfer_proof');
        $refundDate = $request->input('refund_date');

        $refundTx = $this->cashRefundService->recordCashRefund(
            $returnRequest,
            $seller,
            $amount,
            $resolution,
            $notes,
            $proof,
            $refundDate
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Cash resolution recorded.',
                'refundTransaction' => $refundTx,
                'returnRequest'     => $returnRequest->fresh(['evidences', 'refundTransactions']),
            ]);
        }

        return redirect()->back()->with('success', "Cash {$resolution} recorded successfully.");
    }

    /**
     * Seller records cash refund for an order (Store Pickup cash / Special Delivery cash / COD).
     */
    public function orderCashRefund(Request $request, string $orderId)
    {
        $request->validate([
            'refund_amount'  => 'required|numeric|min:0.01',
            'reason'         => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
            'refund_date'    => 'nullable|date',
            'transfer_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $seller = Auth::user();
        $order = \App\Models\Order::findOrFail($orderId);
        $amount = (float) $request->input('refund_amount');
        $reason = $request->input('reason');
        $notes = $request->input('notes');
        $refundDate = $request->input('refund_date');
        $proofFile = $request->file('transfer_proof');
        $paymentMethod = $request->input('payment_method', 'cash');

        $refundTx = $this->cashRefundService->recordOrderCashRefund(
            $order,
            $seller,
            $amount,
            $reason,
            $proofFile,
            $notes,
            $refundDate,
            $paymentMethod
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Cash refund recorded successfully.',
                'refundTransaction' => $refundTx,
                'order'             => $order->fresh(['refundTransactions', 'statusHistories']),
            ]);
        }

        return redirect()->back()->with('success', 'Cash refund of ₱' . number_format($amount, 2) . ' recorded successfully.');
    }
}
