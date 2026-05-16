<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

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

        $report = Report::create([
            'reporterId' => $request->user()->id,
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
