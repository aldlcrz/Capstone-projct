<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function createReport(Request $request)
    {
        $validated = $request->validate([
            'reportedId' => 'required',
            'type' => 'required|in:CustomerReportingSeller,SellerReportingCustomer',
            'reason' => 'required|string',
            'description' => 'required|string',
            'evidence' => 'nullable|string'
        ]);

        $userId = Auth::id() ?? $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated. Please log in.'], 401);
        }

        $report = Report::create([
            'reporterId' => $userId,
            'reportedId' => $validated['reportedId'],
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'evidence' => $validated['evidence'] ?? null,
            'status' => 'Pending'
        ]);

        // Send In-App & System Notifications
        if ($validated['type'] === 'CustomerReportingSeller') {
            // 1. Notify the reported Seller with direct link to report
            Notification::send(
                (string) $validated['reportedId'],
                '⚠️ Integrity Violation Notice',
                "Your shop has received a report from a customer regarding \"{$validated['reason']}\". Our Trust & Safety team is reviewing the matter. Please ensure your shop listings and conduct adhere to platform guidelines.",
                'warning',
                '/seller/reports?view_report=' . $report->id,
                'seller'
            );

            // 2. Alert Platform Admins
            Notification::sendToAdmins(
                '🚩 New Shop Report Filed',
                "A customer reported a seller shop for \"{$validated['reason']}\".",
                'warning',
                '/admin/reports'
            );
        } elseif ($validated['type'] === 'SellerReportingCustomer') {
            // 1. Notify the reported Customer
            Notification::send(
                (string) $validated['reportedId'],
                '⚠️ Account Activity Notice',
                "Your account has received a report from a seller regarding \"{$validated['reason']}\".",
                'warning',
                null,
                'customer'
            );

            // 2. Alert Platform Admins
            Notification::sendToAdmins(
                '🚩 New Customer Report Filed',
                "A seller reported a customer for \"{$validated['reason']}\".",
                'warning',
                '/admin/reports'
            );
        }

        return response()->json($report, 201);
    }

    /**
     * Get report details for the reported seller.
     */
    public function getSellerReportDetail(Request $request, $id = null)
    {
        $userId = Auth::id() ?? $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Report::where('reportedId', $userId);
        if ($id && $id !== 'latest') {
            $report = $query->where('id', $id)->first();
        } else {
            $report = $query->orderBy('createdAt', 'desc')->first();
        }

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json([
            'id' => $report->id,
            'reason' => $report->reason,
            'description' => $report->description,
            'evidence' => $report->evidence,
            'status' => $report->status,
            'type' => $report->type,
            'createdAt' => $report->createdAt ? $report->createdAt->toIso8601String() : null,
            'formattedDate' => $report->createdAt ? $report->createdAt->format('M d, Y h:i A') : '',
        ]);
    }

    public function getReports()
    {
        $reports = Report::with(['reporter:id,name,email', 'reported:id,name,email'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return response()->json($reports);
    }

    /**
     * Seller web page: view all customer reports filed against the logged-in shop.
     */
    public function sellerReportsView(Request $request)
    {
        $sellerId = Auth::id();

        $reports = Report::where('reportedId', $sellerId)
            ->where('type', 'CustomerReportingSeller')
            ->orderBy('createdAt', 'desc')
            ->get();

        $counts = [
            'total'        => $reports->count(),
            'pending'      => $reports->where('status', 'Pending')->count(),
            'under_review' => $reports->where('status', 'Under Review')->count(),
            'resolved'     => $reports->where('status', 'Resolved')->count(),
            'dismissed'    => $reports->where('status', 'Dismissed')->count(),
        ];

        return view('seller.reports.index', compact('reports', 'counts'));
    }
}
