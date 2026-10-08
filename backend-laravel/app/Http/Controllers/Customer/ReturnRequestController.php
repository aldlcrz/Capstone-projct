<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Returns\CreateReturnRequestService;
use App\Services\Returns\DisputeReturnRequestService;
use App\Services\Returns\EvaluateReturnEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function __construct(
        protected CreateReturnRequestService $createService,
        protected DisputeReturnRequestService $disputeService,
        protected EvaluateReturnEligibilityService $eligibilityService
    ) {}

    /**
     * Display the return creation form.
     */
    public function create(Order $order)
    {
        $user = Auth::user();
        $eval = $this->eligibilityService->evaluateEligibility($order, $user);
        
        return view('orders.return-create', compact('order', 'eval'));
    }

    /**
     * Submit a new return/refund request.
     */
    public function store(Request $request, ?string $orderId = null)
    {
        $id = $orderId ?: $request->input('orderId');
        $request->merge(['orderId' => $id]);

        $request->validate([
            'orderId'         => 'required|exists:orders,id',
            'reason'          => 'required|string|min:3',
            'message'         => 'nullable|string',
            'resolution_type' => 'nullable|string|in:refund,exchange,replacement,alteration',
            'proof_files'     => 'nullable|array',
            'proof_files.*'   => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
        ]);

        $customer = Auth::user();
        $files = $request->file('proof_files', []);

        $returnRequest = $this->createService->create($customer, $request->all(), $files);

        if ($request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Return request submitted successfully.',
                'returnRequest' => $returnRequest,
            ], 201);
        }

        return redirect()->route('orders.show', $id)->with('success', 'Return request submitted to the artisan.');
    }

    /**
     * Show return request details.
     */
    public function show(ReturnRequest $returnRequest)
    {
        $user = Auth::user();
        if ($returnRequest->customer_id !== $user->id && $returnRequest->order->customerId !== $user->id) {
            abort(403);
        }

        return response()->json($returnRequest->load(['evidences', 'refundTransactions']));
    }

    /**
     * Customer disputes a seller rejection.
     */
    public function dispute(Request $request, string $order, string $returnRequest)
    {
        $returnReqModel = ReturnRequest::findOrFail($returnRequest);

        $request->validate([
            'reason'      => 'required|string|min:5',
            'proof_files' => 'nullable|array',
        ]);

        $customer = Auth::user();
        $files = $request->file('proof_files', []);

        $updated = $this->disputeService->customerOpenDispute($returnReqModel, $customer, $request->input('reason'), $files);

        if ($request->wantsJson()) {
            return response()->json([
                'success'       => true,
                'message'       => 'Dispute opened and submitted for Admin mediation.',
                'returnRequest' => $updated,
            ]);
        }

        return back()->with('success', 'Dispute opened. An administrator will review your case.');
    }
}
