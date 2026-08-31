<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Submit a new Account or Product Report.
     */
    public function createReport(Request $request)
    {
        $userId = Auth::id() ?? $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated. Please log in to file a report.'], 401);
        }

        $validated = $request->validate([
            'reportedId'  => 'required|string',
            'type'        => 'nullable|string',
            'reportType'  => 'nullable|in:account,product',
            'productId'   => 'nullable|string',
            'reason'      => 'required|string|max:255',
            'description' => 'required|string|min:10|max:5000',
            'evidence'    => 'nullable',
        ]);

        $reportedUser = User::find($validated['reportedId']);
        if (!$reportedUser) {
            return response()->json(['message' => 'The account you are reporting could not be found.'], 404);
        }

        // Prevent self-reporting
        if ($userId === $reportedUser->id) {
            return response()->json(['message' => 'You cannot file a report against your own account.'], 422);
        }

        $reportType = $validated['reportType'] ?? (!empty($validated['productId']) ? 'product' : 'account');
        $productId  = !empty($validated['productId']) ? $validated['productId'] : null;

        if ($reportType === 'product' && $productId) {
            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['message' => 'The reported product could not be found.'], 404);
            }
        }

        // Anti-spam / Duplicate Check: prevent duplicate active pending reports within 24 hours
        $existingPending = Report::where('reporterId', $userId)
            ->where('reportedId', $reportedUser->id)
            ->where('reportType', $reportType)
            ->when($productId, fn($q) => $q->where('productId', $productId))
            ->where('reason', $validated['reason'])
            ->whereIn('status', ['Pending', 'Under Review'])
            ->where('createdAt', '>=', now()->subHours(24))
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'You have already submitted an active report for this concern within the last 24 hours. Our Trust & Safety team is actively reviewing it.',
                'report_id' => $existingPending->id,
            ], 429);
        }

        // Normalize evidence (handle array or string)
        $evidenceRaw = $validated['evidence'] ?? null;
        if (is_array($evidenceRaw)) {
            $evidenceStr = json_encode(array_values(array_filter($evidenceRaw)));
        } else {
            $evidenceStr = !empty($evidenceRaw) ? (string) $evidenceRaw : null;
        }

        $typeLegacy = $validated['type'] ?? ($reportedUser->role === 'seller' ? 'CustomerReportingSeller' : 'SellerReportingCustomer');

        $report = Report::create([
            'reporterId'   => $userId,
            'reportedId'   => $reportedUser->id,
            'type'         => $typeLegacy,
            'reportType'   => $reportType,
            'productId'    => $productId,
            'reason'       => $validated['reason'],
            'description'  => $validated['description'],
            'evidence'     => $evidenceStr,
            'severity'     => 'MEDIUM',
            'status'       => 'Pending',
        ]);

        $currentUser = Auth::user() ?? $request->user();

        // Add Milestone 1: Report Submitted
        $targetLabel = $reportType === 'product' ? 'product listing' : 'seller account';
        $report->addTimelineEvent(
            'report_submitted',
            'Report Submitted',
            "A concern regarding \"{$report->reason}\" was filed by a platform member.",
            $currentUser,
            $currentUser->role ?? 'customer'
        );

        // Add Milestone 2: Received by Trust & Safety
        $report->addTimelineEvent(
            'received',
            'Received by Trust & Safety',
            'Case opened and queued for moderation review.',
            null,
            'system'
        );

        // Send In-App & System Notifications
        if ($reportedUser->role === 'seller') {
            // 1. Notify Seller
            Notification::send(
                (string) $reportedUser->id,
                '⚠️ Concern Filed Under Review',
                "Your {$targetLabel} received a customer concern regarding \"{$validated['reason']}\". Our Trust & Safety team is reviewing the matter. You may view details and submit a response in your Reports portal.",
                'warning',
                '/seller/reports?view_report=' . $report->id,
                'seller'
            );

            // 2. Alert Platform Admins
            Notification::sendToAdmins(
                '🚩 New Trust & Safety Report Filed',
                "A customer reported a {$targetLabel} ({$reportedUser->shopName}) for \"{$validated['reason']}\".",
                'warning',
                '/admin/reports'
            );
        } else {
            Notification::send(
                (string) $reportedUser->id,
                '⚠️ Account Activity Notice',
                "Your account has received a concern report regarding \"{$validated['reason']}\".",
                'warning',
                null,
                'customer'
            );

            Notification::sendToAdmins(
                '🚩 New Account Report Filed',
                "A report was filed against user account ({$reportedUser->name}) for \"{$validated['reason']}\".",
                'warning',
                '/admin/reports'
            );
        }

        // Notify reporter of successful submission
        Notification::send(
            (string) $userId,
            '🛡️ Report Submitted Successfully',
            "Your report ({$report->getReportCode()}) has been received and is waiting for review by our Trust & Safety team.",
            'info',
            '/profile/reports',
            'customer'
        );

        return response()->json([
            'status'     => 'success',
            'message'    => 'Your report has been submitted successfully. Our Trust & Safety team will review your concern.',
            'report'     => $report,
            'report_id'  => $report->id,
            'report_code'=> $report->getReportCode(),
        ], 201);
    }

    /**
     * Submit Seller Response to an open report.
     */
    public function submitSellerResponse(Request $request, string $id)
    {
        $sellerId = Auth::id();
        if (!$sellerId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $report = Report::findOrFail($id);

        if ($report->reportedId !== $sellerId) {
            return response()->json(['message' => 'Unauthorized. You can only respond to reports filed against your shop.'], 403);
        }

        $validated = $request->validate([
            'response' => 'required|string|min:5|max:3000',
            'evidence' => 'nullable',
        ]);

        $evidenceRaw = $validated['evidence'] ?? null;
        if (is_array($evidenceRaw)) {
            $evidenceStr = json_encode(array_values(array_filter($evidenceRaw)));
        } else {
            $evidenceStr = !empty($evidenceRaw) ? (string) $evidenceRaw : null;
        }

        $report->sellerResponse = $validated['response'];
        $report->sellerResponseEvidence = $evidenceStr;
        $report->sellerRespondedAt = now();

        if ($report->status === 'Pending') {
            $report->status = 'Under Review';
        }
        $report->save();

        // Add timeline event
        $report->addTimelineEvent(
            'seller_response',
            'Seller Response Submitted',
            'The seller provided additional context and clarification regarding this concern.',
            Auth::user(),
            'seller'
        );

        // Notify Admins
        Notification::sendToAdmins(
            '💬 Seller Responded to Report',
            "Seller ({$report->reported->shopName}) provided an official response to case {$report->getReportCode()}.",
            'info',
            '/admin/reports'
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Your response has been added to the case record.',
                'report'  => $report,
            ]);
        }

        return redirect()->back()->with('success', 'Your response has been submitted and added to the case record.');
    }

    /**
     * Get report details JSON for modal viewer.
     */
    public function getSellerReportDetail(Request $request, $id = null)
    {
        $userId = Auth::id() ?? $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Report::with(['product', 'timelineEvents.actor'])
            ->where(function($q) use ($userId) {
                $q->where('reportedId', $userId)->orWhere('reporterId', $userId);
            });

        if ($id && $id !== 'latest') {
            $report = $query->where('id', $id)->first();
        } else {
            $report = $query->orderBy('createdAt', 'desc')->first();
        }

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json([
            'id'                  => $report->id,
            'reportCode'          => $report->getReportCode(),
            'reportType'          => $report->reportType ?? 'account',
            'reason'              => $report->reason,
            'description'         => $report->description,
            'evidence'            => $report->getEvidenceList(),
            'severity'            => $report->severity ?? 'MEDIUM',
            'status'              => $report->status,
            'adminNotes'          => $report->adminNotes,
            'investigationResult' => $report->investigationResult,
            'disciplinaryReason'  => $report->disciplinaryReason,
            'actionTaken'         => $report->actionTaken,
            'sellerResponse'      => $report->sellerResponse,
            'sellerResponseEvidence' => $report->getSellerEvidenceList(),
            'sellerRespondedAt'   => $report->sellerRespondedAt ? $report->sellerRespondedAt->format('M d, Y h:i A') : null,
            'product'             => $report->product ? [
                'id'    => $report->product->id,
                'name'  => $report->product->name,
                'price' => $report->product->price,
                'image' => $report->product->primary_image ?? '/uploads/products/default.jpg',
            ] : null,
            'timeline'            => $report->timelineEvents->map(fn($t) => [
                'id'          => $t->id,
                'event_type'  => $t->event_type,
                'title'       => $t->title,
                'description' => $t->description,
                'actor_role'  => $t->actor_role,
                'date'        => $t->created_at->format('M d, Y • h:i A'),
                'time_ago'    => $t->created_at->diffForHumans(),
            ]),
            'createdAt'           => $report->createdAt ? $report->createdAt->toIso8601String() : null,
            'formattedDate'       => $report->createdAt ? $report->createdAt->format('M d, Y h:i A') : '',
        ]);
    }

    /**
     * Seller web page: view all customer reports filed against the logged-in shop.
     */
    public function sellerReportsView(Request $request)
    {
        $sellerId = Auth::id();

        $typeFilter   = $request->query('type', 'all');
        $statusFilter = $request->query('status', 'all');
        $search       = trim($request->query('search', ''));

        $query = Report::with(['product', 'timelineEvents'])
            ->where('reportedId', $sellerId)
            ->where('type', 'CustomerReportingSeller');

        if ($typeFilter !== 'all') {
            $query->where('reportType', $typeFilter);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderBy('createdAt', 'desc')->get();

        // Calculate metric counts across all seller reports
        $allReports = Report::where('reportedId', $sellerId)->where('type', 'CustomerReportingSeller')->get();

        $counts = [
            'total'                => $allReports->count(),
            'account_reports'      => $allReports->where('reportType', 'account')->count(),
            'product_reports'      => $allReports->where('reportType', 'product')->count(),
            'pending'              => $allReports->where('status', 'Pending')->count(),
            'under_review'         => $allReports->where('status', 'Under Review')->count(),
            'resolved'             => $allReports->where('status', 'Resolved')->count(),
            'dismissed'            => $allReports->where('status', 'Dismissed')->count(),
            'confirmed_violations' => $allReports->where('investigationResult', 'Policy Violation Confirmed')->count(),
        ];

        return view('seller.reports.index', compact('reports', 'counts', 'typeFilter', 'statusFilter', 'search'));
    }

    /**
     * Customer web page: view all reports submitted by the customer.
     */
    public function customerReportsView(Request $request)
    {
        $userId = Auth::id();

        $reports = Report::with(['reported', 'product', 'timelineEvents'])
            ->where('reporterId', $userId)
            ->orderBy('createdAt', 'desc')
            ->paginate(10);

        return view('profile.my-reports', compact('reports'));
    }

    /**
     * List all reports for API.
     */
    public function getReports()
    {
        $reports = Report::with(['reporter:id,name,email', 'reported:id,name,email', 'product'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return response()->json($reports);
    }
}
