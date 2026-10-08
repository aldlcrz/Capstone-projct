<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\Returns\DisputeReturnRequestService;
use App\Services\Returns\ProcessPlatformRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnManagementController extends Controller
{
    public function __construct(
        protected ProcessPlatformRefundService $refundService,
        protected DisputeReturnRequestService $disputeService
    ) {}

    /**
     * List all return/refund cases for Admin & SuperAdmin.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = ReturnRequest::with(['order', 'customer', 'seller', 'evidences', 'refundTransactions'])
            ->orderBy('createdAt', 'desc');

        if ($status) {
            $query->where('return_status', $status);
        }

        $returns = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($returns);
        }

        return view('admin.returns.index', compact('returns'));
    }

    /**
     * Show detailed case view.
     */
    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['order.paymentTransactions', 'order.items', 'customer', 'seller', 'evidences', 'refundTransactions']);
        
        return response()->json($returnRequest);
    }

    /**
     * Admin approves and records centralized platform refund disbursement.
     */
    public function recordTransfer(Request $request, ReturnRequest $returnRequest)
    {
        $request->validate([
            'refund_amount'       => 'required|numeric|min:0.01',
            'transfer_reference'  => 'required|string|min:4|max:100',
            'transfer_proof'      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'destination_account' => 'nullable|string|max:100',
            'destination_name'    => 'nullable|string|max:150',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $admin = Auth::user();
        $amount = (float) $request->input('refund_amount');
        $ref = $request->input('transfer_reference');
        $proofFile = $request->file('transfer_proof');
        $destAcc = $request->input('destination_account');
        $destName = $request->input('destination_name');
        $notes = $request->input('notes');

        $refundTx = $this->refundService->processPlatformRefund(
            $returnRequest,
            $admin,
            $amount,
            $ref,
            $proofFile,
            $destAcc,
            $destName,
            $notes
        );

        return response()->json([
            'success'           => true,
            'message'           => 'Platform refund transfer processed and recorded successfully.',
            'refundTransaction' => $refundTx,
            'returnRequest'     => $returnRequest->fresh(['evidences', 'refundTransactions']),
        ]);
    }

    /**
     * Admin resolves a dispute.
     */
    public function resolveDispute(Request $request, ReturnRequest $returnRequest)
    {
        $request->validate([
            'decision' => 'required|in:approve_return,uphold_rejection',
            'notes'    => 'nullable|string|max:1000',
        ]);

        $admin = Auth::user();
        $decision = $request->input('decision');
        $notes = $request->input('notes');

        $updated = $this->disputeService->adminResolveDispute($returnRequest, $admin, $decision, $notes);

        return response()->json([
            'success'       => true,
            'message'       => 'Dispute resolved.',
            'returnRequest' => $updated,
        ]);
    }
}
