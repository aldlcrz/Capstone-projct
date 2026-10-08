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
        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $query = ReturnRequest::with(['order', 'customer', 'seller', 'evidences', 'refundTransactions']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('orderId', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('seller', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('shopName', 'like', "%{$search}%");
                  });
            });
        }

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'pending') {
                $query->whereIn('return_status', ['pending', 'requested', 'in_review']);
            } elseif ($status === 'disputed') {
                $query->whereIn('return_status', ['disputed', 'escalated']);
            } elseif ($status === 'approved') {
                $query->whereIn('return_status', ['approved', 'item_shipped', 'item_received']);
            } elseif ($status === 'refunded') {
                $query->whereIn('return_status', ['refunded', 'completed', 'resolved']);
            } elseif ($status === 'rejected') {
                $query->whereIn('return_status', ['rejected', 'declined']);
            } else {
                $query->where('return_status', $status);
            }
        }

        $returns = $query->orderBy('createdAt', 'desc')->paginate(15)->withQueryString();

        $counts = [
            'all'      => ReturnRequest::count(),
            'pending'  => ReturnRequest::whereIn('return_status', ['pending', 'requested', 'in_review'])->count(),
            'disputed' => ReturnRequest::whereIn('return_status', ['disputed', 'escalated'])->count(),
            'approved' => ReturnRequest::whereIn('return_status', ['approved', 'item_shipped', 'item_received'])->count(),
            'refunded' => ReturnRequest::whereIn('return_status', ['refunded', 'completed', 'resolved'])->count(),
            'rejected' => ReturnRequest::whereIn('return_status', ['rejected', 'declined'])->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($returns);
        }

        return view('admin.returns.index', compact('returns', 'counts', 'status', 'search'));
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

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Platform refund transfer processed and recorded successfully.',
                'refundTransaction' => $refundTx,
                'returnRequest'     => $returnRequest->fresh(['evidences', 'refundTransactions']),
            ]);
        }

        return redirect()->back()->with('success', 'Platform refund transfer of ₱' . number_format($amount, 2) . ' recorded successfully.');
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

        if ($request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Dispute resolved successfully.',
                'returnRequest' => $updated,
            ]);
        }

        $decisionLabel = $decision === 'approve_return' ? 'approved for customer return' : 'seller rejection upheld';
        return redirect()->back()->with('success', "Dispute for Case #RR-" . strtoupper(substr($returnRequest->id, -8)) . " resolved ({$decisionLabel}).");
    }
}
