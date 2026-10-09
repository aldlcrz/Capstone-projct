<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Returns\DisputeReturnRequestService;
use App\Services\Returns\ProcessCancellationRefundService;
use App\Services\Returns\ProcessPlatformRefundService;
use App\Services\Returns\ProcessSukliRefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnManagementController extends Controller
{
    public function __construct(
        protected ProcessPlatformRefundService $refundService,
        protected DisputeReturnRequestService $disputeService,
        protected ProcessSukliRefundService $sukliRefundService,
        protected ProcessCancellationRefundService $cancellationRefundService
    ) {}

    /**
     * Centralized Return & Refund Center with 3 distinct workflows:
     * 1. Return & Refund Claims
     * 2. Sukli / Overpayment Refunds
     * 3. Order Cancellations
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'claims');
        if (!in_array($tab, ['claims', 'sukli', 'cancellations'], true)) {
            $tab = 'claims';
        }

        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $returns = null;
        $sukliOrders = null;
        $cancellations = null;
        $counts = [];

        if ($tab === 'claims') {
            // ═════════════════════════════════════════════════════════════════
            // 1. RETURN & REFUND CLAIMS WORKFLOW
            // ═════════════════════════════════════════════════════════════════
            $query = ReturnRequest::with(['order.paymentTransactions', 'customer', 'seller', 'evidences', 'refundTransactions']);

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

            $returns = $query->orderBy('createdAt', 'desc')->paginate(15, ['*'], 'claims_page')->withQueryString();

            $counts = [
                'all'      => ReturnRequest::count(),
                'disputed' => ReturnRequest::whereIn('return_status', ['disputed', 'escalated'])->count(),
                'pending'  => ReturnRequest::whereIn('return_status', ['pending', 'requested', 'in_review'])->count(),
                'approved' => ReturnRequest::whereIn('return_status', ['approved', 'item_shipped', 'item_received'])->count(),
                'refunded' => ReturnRequest::whereIn('return_status', ['refunded', 'completed', 'resolved'])->count(),
                'rejected' => ReturnRequest::whereIn('return_status', ['rejected', 'declined'])->count(),
            ];
        } elseif ($tab === 'sukli') {
            // ═════════════════════════════════════════════════════════════════
            // 2. SUKLI / OVERPAYMENT REFUNDS WORKFLOW
            // ═════════════════════════════════════════════════════════════════
            // Find all orders that have payment transactions or refund transactions
            $query = Order::with(['customer', 'seller', 'paymentTransactions', 'refundTransactions.processor', 'statusHistories.updater'])
                ->where(function ($q) {
                    $q->whereHas('paymentTransactions', function ($pq) {
                        $pq->where('detected_amount', '>', 0);
                    })->orWhereHas('refundTransactions', function ($rq) {
                        $rq->whereNull('return_request_id');
                    });
                });

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('paymentTransactions', function ($pq) use ($search) {
                          $pq->where('reference_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('refundTransactions', function ($rq) use ($search) {
                          $rq->where('transfer_reference', 'like', "%{$search}%");
                      });
                });
            }

            // Get all candidate orders to accurately calculate authoritative sukli
            $allCandidates = (clone $query)->orderBy('createdAt', 'desc')->get();
            $sukliCandidates = $allCandidates->filter(function (Order $ord) {
                return $ord->authoritativeSukliAmount() > 0 || $ord->refundTransactions->whereNull('return_request_id')->count() > 0;
            });

            // Filter by sukli status
            $filtered = $sukliCandidates;
            if ($status !== 'all' && !empty($status)) {
                if ($status === 'pending_refund') {
                    $filtered = $sukliCandidates->filter(function (Order $ord) {
                        $isVerified = in_array(strtolower($ord->paymentStatus ?? ''), ['paid', 'verified'], true)
                            || ($ord->latestPaymentTransaction && $ord->latestPaymentTransaction->status === 'VERIFIED');
                        return $isVerified && $ord->remainingSukliRefundAmount() > 0;
                    });
                } elseif ($status === 'refunded') {
                    $filtered = $sukliCandidates->filter(function (Order $ord) {
                        return $ord->sukliRefundStatus() === 'refunded';
                    });
                } elseif ($status === 'pending_verification') {
                    $filtered = $sukliCandidates->filter(function (Order $ord) {
                        $isVerified = in_array(strtolower($ord->paymentStatus ?? ''), ['paid', 'verified'], true)
                            || ($ord->latestPaymentTransaction && $ord->latestPaymentTransaction->status === 'VERIFIED');
                        return !$isVerified && $ord->authoritativeSukliAmount() > 0;
                    });
                }
            }

            // Paginate the collection manually for flawless presentation
            $page = max(1, (int) $request->query('sukli_page', 1));
            $perPage = 15;
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
            $sukliOrders = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'sukli_page', 'query' => $request->query()]
            );

            $counts = [
                'all'                  => $sukliCandidates->count(),
                'pending_refund'       => $sukliCandidates->filter(fn(Order $o) => in_array(strtolower($o->paymentStatus ?? ''), ['paid', 'verified'], true) && $o->remainingSukliRefundAmount() > 0)->count(),
                'refunded'             => $sukliCandidates->filter(fn(Order $o) => $o->sukliRefundStatus() === 'refunded')->count(),
                'pending_verification' => $sukliCandidates->filter(fn(Order $o) => !in_array(strtolower($o->paymentStatus ?? ''), ['paid', 'verified'], true) && $o->authoritativeSukliAmount() > 0)->count(),
            ];
        } elseif ($tab === 'cancellations') {
            // ═════════════════════════════════════════════════════════════════
            // 3. ORDER CANCELLATIONS WORKFLOW
            // ═════════════════════════════════════════════════════════════════
            $query = Order::with(['customer', 'seller', 'paymentTransactions', 'refundTransactions.processor', 'statusHistories.updater'])
                ->where(function ($q) {
                    $q->whereIn('status', ['cancelled', 'cancellation_pending'])
                      ->orWhereNotNull('cancellationReason');
                });

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('cancellationReason', 'like', "%{$search}%")
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

            $allCancellations = (clone $query)->orderBy('updatedAt', 'desc')->get();

            // Filter by cancellation status
            $filtered = $allCancellations;
            if ($status !== 'all' && !empty($status)) {
                if ($status === 'pending_refund') {
                    $filtered = $allCancellations->filter(function (Order $ord) {
                        return $ord->cancellationRefundStatus() === 'pending_refund';
                    });
                } elseif ($status === 'refunded') {
                    $filtered = $allCancellations->filter(function (Order $ord) {
                        return $ord->cancellationRefundStatus() === 'refunded';
                    });
                } elseif ($status === 'unpaid') {
                    $filtered = $allCancellations->filter(function (Order $ord) {
                        return $ord->cancellationRefundStatus() === 'unpaid';
                    });
                } elseif ($status === 'pending_approval') {
                    $filtered = $allCancellations->filter(function (Order $ord) {
                        return strtolower($ord->status) === 'cancellation_pending';
                    });
                }
            }

            $page = max(1, (int) $request->query('cancel_page', 1));
            $perPage = 15;
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
            $cancellations = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'cancel_page', 'query' => $request->query()]
            );

            $counts = [
                'all'              => $allCancellations->count(),
                'pending_refund'   => $allCancellations->filter(fn(Order $o) => $o->cancellationRefundStatus() === 'pending_refund')->count(),
                'refunded'         => $allCancellations->filter(fn(Order $o) => $o->cancellationRefundStatus() === 'refunded')->count(),
                'unpaid'           => $allCancellations->filter(fn(Order $o) => $o->cancellationRefundStatus() === 'unpaid')->count(),
                'pending_approval' => $allCancellations->filter(fn(Order $o) => strtolower($o->status) === 'cancellation_pending')->count(),
            ];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'tab'           => $tab,
                'counts'        => $counts,
                'returns'       => $returns,
                'sukliOrders'   => $sukliOrders,
                'cancellations' => $cancellations,
            ]);
        }

        return view('admin.returns.index', compact(
            'tab',
            'returns',
            'sukliOrders',
            'cancellations',
            'counts',
            'status',
            'search'
        ));
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
     * Admin approves and records centralized platform refund disbursement for claims.
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
     * Admin resolves a return dispute.
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

    /**
     * Admin or SuperAdmin processes and records a sukli / overpayment refund transfer.
     */
    public function processSukliRefund(Request $request, string $id)
    {
        $request->validate([
            'refund_amount'       => 'required|numeric|min:0.01',
            'transfer_reference'  => 'required|string|min:3|max:100',
            'destination_account' => 'nullable|string|max:100',
            'destination_name'    => 'nullable|string|max:150',
            'transfer_proof'      => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $admin = Auth::user();
        if (!$admin || !in_array($admin->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Unauthorized access.');
        }

        $refundTx = $this->sukliRefundService->processSukliRefund(
            orderId: $id,
            admin: $admin,
            refundAmount: (float) $request->input('refund_amount'),
            transferReference: trim((string) $request->input('transfer_reference')),
            transferProofFile: $request->file('transfer_proof'),
            destinationAccount: $request->input('destination_account'),
            destinationName: $request->input('destination_name'),
            adminNotes: $request->input('notes')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Sukli refund transfer recorded successfully and customer notified.',
                'refundTransaction' => $refundTx,
            ]);
        }

        return redirect()->back()->with('success', 'Sukli refund of ₱' . number_format((float) $refundTx->refund_amount, 2) . ' recorded successfully. Official LumBarong inbox notification sent to customer.');
    }

    /**
     * Admin or SuperAdmin processes and records a full cancellation refund transfer.
     */
    public function processCancellationRefund(Request $request, string $id)
    {
        $request->validate([
            'refund_amount'       => 'required|numeric|min:0.01',
            'transfer_reference'  => 'required|string|min:3|max:100',
            'destination_account' => 'nullable|string|max:100',
            'destination_name'    => 'nullable|string|max:150',
            'transfer_proof'      => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:10240',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $admin = Auth::user();
        if (!$admin || !in_array($admin->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Unauthorized access.');
        }

        $refundTx = $this->cancellationRefundService->processCancellationRefund(
            orderId: $id,
            admin: $admin,
            refundAmount: (float) $request->input('refund_amount'),
            transferReference: trim((string) $request->input('transfer_reference')),
            transferProofFile: $request->file('transfer_proof'),
            destinationAccount: $request->input('destination_account'),
            destinationName: $request->input('destination_name'),
            adminNotes: $request->input('notes')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'message'           => 'Cancellation refund transfer recorded successfully and customer notified.',
                'refundTransaction' => $refundTx,
            ]);
        }

        return redirect()->back()->with('success', 'Cancellation refund of ₱' . number_format((float) $refundTx->refund_amount, 2) . ' recorded successfully. Official LumBarong inbox notification sent to customer.');
    }
}
