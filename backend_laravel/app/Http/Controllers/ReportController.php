<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Models\Notification;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function createReport(Request $request)
    {
        try {
            $reportedId = $request->input('reportedId');
            $type = $request->input('type');
            $reason = $request->input('reason');
            $description = $request->input('description');
            $referenceId = $request->input('referenceId');
            $reporterId = Auth::id();

            if (!$reportedId || !$type || !$reason || !$description) {
                return response()->json(['message' => 'Missing required fields for report.'], 400);
            }

            $evidenceUrls = [];
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $fileName = 'report_' . time() . '_' . Str::random(5) . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/reports'), $fileName);
                    $evidenceUrls[] = "/uploads/reports/{$fileName}";
                }
            }

            $report = Report::create([
                'reporterId' => $reporterId,
                'reportedId' => $reportedId,
                'type' => $type,
                'reason' => $reason,
                'description' => $description,
                'referenceId' => $referenceId ?: null,
                'evidence' => count($evidenceUrls) > 0 ? $evidenceUrls : null,
                'status' => 'Pending'
            ]);

            $reportedUser = User::find($reportedId);
            $reporterUser = User::find($reporterId);
            $typeLabel = $type === 'CustomerReportingSeller' ? 'Customer reported a Seller' : 'Seller reported a Customer';

            // Notify admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'userId' => $admin->id,
                    'title' => "🚨 New Report: {$typeLabel}",
                    'message' => ($reporterUser ? $reporterUser->name : 'A user') . " filed a report against " . ($reportedUser ? $reportedUser->name : 'a user') . ". Reason: {$reason}",
                    'type' => 'system',
                    'link' => '/admin/reports',
                    'targetRole' => 'admin'
                ]);
                SocketUtility::emitToUser($admin->id, 'new_notification', [
                    'title' => "🚨 New Report: {$typeLabel}",
                    'message' => ($reporterUser ? $reporterUser->name : 'A user') . " filed a report against " . ($reportedUser ? $reportedUser->name : 'a user') . "."
                ]);
            }

            SocketUtility::emitStatsUpdate(['type' => 'report']);

            return response()->json([
                'message' => 'Report submitted successfully.',
                'data' => $report
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal server error while creating report.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyReports()
    {
        try {
            $reports = Report::where('reporterId', Auth::id())
                ->with('reportedUser:id,name,email,role')
                ->orderBy('createdAt', 'DESC')
                ->get();

            return response()->json(['data' => $reports]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error while fetching reports.'], 500);
        }
    }

    public function getAllReportsAdmin()
    {
        try {
            $reports = Report::with([
                'reporter:id,name,email,role',
                'reportedUser:id,name,email,role,status'
            ])
            ->orderBy('createdAt', 'DESC')
            ->get();

            return response()->json(['data' => $reports]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error while fetching all reports.'], 500);
        }
    }

    public function resolveReport(Request $request, string $id)
    {
        try {
            $status = $request->input('status');
            $adminNotes = $request->input('adminNotes');
            $actionTaken = $request->input('actionTaken');

            $report = Report::with('reportedUser')->find($id);
            if (!$report) {
                return response()->json(['message' => 'Report not found.'], 404);
            }

            $report->update([
                'status' => $status ?? $report->status,
                'adminNotes' => $adminNotes ?? $report->adminNotes,
                'actionTaken' => $actionTaken ?? $report->actionTaken
            ]);

            if ($actionTaken === 'Suspended' || $actionTaken === 'Restricted') {
                if ($report->reportedUser) {
                    $report->reportedUser->update(['status' => 'blocked']);
                }
            }

            return response()->json([
                'message' => 'Report resolved successfully.',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error while resolving report.'], 500);
        }
    }
}
