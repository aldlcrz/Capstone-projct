<?php

namespace App\Http\Controllers;

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

        return response()->json($report, 201);
    }

    public function getReports()
    {
        $reports = Report::with(['reporter:id,name,email', 'reported:id,name,email'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return response()->json($reports);
    }
}
