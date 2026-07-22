<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ArtisanBadge;
use App\Models\SystemAuditLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{
    /**
     * Get dynamic commission settings per category
     */
    public function getCommissionRules()
    {
        $setting = SystemSetting::where('key', 'category_commissions')->first();
        $rules = $setting ? json_decode($setting->value, true) : [
            'Barong Tagalog' => 10.0,
            'Filipiniana' => 10.0,
            'Accessories' => 8.0,
            'Custom Hand-Embroidered' => 5.0,
            'Default' => 10.0,
        ];

        return response()->json([
            'status' => 'success',
            'rules' => $rules,
        ]);
    }

    /**
     * Update dynamic commission settings per category
     */
    public function updateCommissionRules(Request $request)
    {
        $rules = $request->input('rules', []);
        SystemSetting::updateOrCreate(
            ['key' => 'category_commissions'],
            ['value' => json_encode($rules)]
        );

        // Audit Log
        SystemAuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_COMMISSION_RULES',
            'entity_type' => 'SystemSetting',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'details' => ['rules' => $rules],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Commission rules updated successfully.',
            'rules' => $rules,
        ]);
    }

    /**
     * Get list of artisan sellers with their heritage badges
     */
    public function getBadges()
    {
        $sellers = User::where('role', 'seller')
            ->select('id', 'name', 'email', 'shopName', 'profilePhoto', 'isVerified', 'createdAt')
            ->with('artisanBadges')
            ->get();

        return response()->json([
            'status' => 'success',
            'sellers' => $sellers,
        ]);
    }

    /**
     * Assign or revoke a heritage badge for a seller
     */
    public function toggleArtisanBadge(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
            'badge_type' => 'required|string',
        ]);

        $sellerId = $request->input('seller_id');
        $badgeType = $request->input('badge_type');

        $existing = ArtisanBadge::where('seller_id', $sellerId)
            ->where('badge_type', $badgeType)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'REVOKE_ARTISAN_BADGE';
            $message = 'Badge revoked successfully.';
        } else {
            ArtisanBadge::create([
                'seller_id' => $sellerId,
                'badge_type' => $badgeType,
                'issued_by' => Auth::id(),
            ]);
            $action = 'ASSIGN_ARTISAN_BADGE';
            $message = 'Badge assigned successfully.';
        }

        // Audit Log
        SystemAuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => 'ArtisanBadge',
            'entity_id' => $sellerId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'details' => ['seller_id' => $sellerId, 'badge_type' => $badgeType],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'badges' => ArtisanBadge::where('seller_id', $sellerId)->get(),
        ]);
    }

    /**
     * Get paginated audit logs
     */
    public function getAuditLogs(Request $request)
    {
        $query = SystemAuditLog::with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');

        if ($request->has('action') && !empty($request->query('action'))) {
            $query->where('action', $request->query('action'));
        }

        $logs = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'logs' => $logs,
        ]);
    }

    /**
     * Get system health and diagnostic statistics
     */
    public function getSystemHealth()
    {
        $dbName = DB::connection()->getDatabaseName();
        $userCount = DB::table('users')->count();
        $productCount = DB::table('products')->count();
        $orderCount = DB::table('orders')->count();
        $logCount = DB::table('system_audit_logs')->count();

        // Calculate database table sizes if MySQL
        $dbSize = 'N/A';
        try {
            $sizeResult = DB::select("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
            if (!empty($sizeResult) && isset($sizeResult[0]->size_mb)) {
                $dbSize = round($sizeResult[0]->size_mb, 2) . ' MB';
            }
        } catch (\Exception $e) {
            // Fallback for non-MySQL connection
        }

        return response()->json([
            'status' => 'success',
            'health' => [
                'databaseName' => $dbName,
                'databaseSize' => $dbSize,
                'phpVersion' => PHP_VERSION,
                'laravelVersion' => app()->version(),
                'serverOS' => PHP_OS_FAMILY,
                'counts' => [
                    'users' => $userCount,
                    'products' => $productCount,
                    'orders' => $orderCount,
                    'auditLogs' => $logCount,
                ],
            ],
        ]);
    }

    /**
     * Download a complete SQL dump backup of the database
     */
    public function downloadDatabaseBackup(Request $request)
    {
        // Audit log
        SystemAuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'DOWNLOAD_DB_BACKUP',
            'entity_type' => 'Database',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::connection()->getDatabaseName();
        $tableKey = "Tables_in_" . $dbName;

        $sql = "-- Lumbarong Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->$tableKey ?? current((array)$tableObj);
            
            // Create statement
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
            $createSql = $createTable[0]->{'Create Table'} ?? '';
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sql .= $createSql . ";\n\n";

            // Data
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $rowArray = (array)$row;
                $keys = array_keys($rowArray);
                $escapedValues = array_map(function ($val) {
                    if (is_null($val)) return 'NULL';
                    return "'" . addslashes($val) . "'";
                }, array_values($rowArray));

                $sql .= "INSERT INTO `$tableName` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = "lumbarong_backup_" . date('Y_m_d_His') . ".sql";

        return Response::make($sql, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
