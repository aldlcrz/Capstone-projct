<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class AdminSettingsController extends Controller
{
    // ── System Settings ──────────────────────────────────────────────────────

    public function index()
    {
        $settings = SystemSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value]; // value is decoded by json cast
        })->toArray(); // convert to plain array so string defaults can be merged freely

        // Provide sensible defaults for any missing keys
        $defaults = [
            'site_name'            => 'LumBarong',
            'site_tagline'         => 'Authentic Filipino Barong Marketplace',
            'support_email'        => 'support@lumbarong.com',
            'max_banner_per_seller'=> '3',
            'commission_rate'      => '10',
            'min_withdrawal'       => '500',
            'allow_registration'   => '1',
            'allow_seller_signup'  => '1',
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('admin.settings')->with('success', 'Settings saved successfully.');
    }

    // ── Maintenance Mode ─────────────────────────────────────────────────────

    /**
     * Returns true if either Laravel's native maintenance file exists
     * OR the admin has set the DB flag (used as fallback when artisan down is restricted).
     */
    private function isInMaintenance(): bool
    {
        if (app()->isDownForMaintenance()) {
            return true;
        }
        $flag = SystemSetting::where('key', 'maintenance_mode')->first()?->value;
        return $flag === '1' || $flag === true || $flag === 1;
    }

    public function maintenance()
    {
        $isMaintenanceMode = $this->isInMaintenance();

        // Use model so the json cast decodes the stored JSON value to a plain string
        $maintenanceMessage = SystemSetting::where('key', 'maintenance_message')->first()?->value
            ?? 'We are currently performing scheduled maintenance. We\'ll be back shortly.';
        $scheduledAt  = SystemSetting::where('key', 'maintenance_scheduled_at')->first()?->value;
        $scheduledEnd = SystemSetting::where('key', 'maintenance_scheduled_end')->first()?->value;

        return view('admin.settings.maintenance', compact(
            'isMaintenanceMode', 'maintenanceMessage', 'scheduledAt', 'scheduledEnd'
        ));
    }

    public function toggleMaintenance(Request $request)
    {
        $enable  = $request->input('enable');
        $message = $request->input('message', 'Scheduled maintenance in progress.');

        // Always persist the message
        SystemSetting::updateOrCreate(['key' => 'maintenance_message'], ['value' => $message]);

        if ($enable === '1') {
            // Mark active in DB first (reliable fallback regardless of artisan)
            SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
            try {
                Artisan::call('down', ['--render' => 'errors.503', '--secret' => 'lumbarong-admin']);
            } catch (\Exception $e) {
                // artisan down failed — DB flag is already set, that's our fallback
            }
            return redirect()->route('admin.maintenance')->with('success', 'Maintenance mode is now ACTIVE. Regular users will see the maintenance page.');
        } else {
            // Clear DB flag first
            SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '0']);
            try {
                Artisan::call('up');
            } catch (\Exception $e) {
                // ignore — DB flag is cleared so admin panel shows Online
            }
            return redirect()->route('admin.maintenance')->with('success', 'Site is back ONLINE. All users can access the platform normally.');
        }
    }

    // ── Audit Logs ───────────────────────────────────────────────────────────

    public function auditLogs(Request $request)
    {
        $search  = $request->input('search');
        $tab     = $request->input('tab', 'all'); // all | orders | products | users
        $perPage = 25;
        $page    = (int) $request->input('page', 1);

        $needle = $search ? strtolower((string) $search) : null;

        $filterFn = function (\Illuminate\Support\Collection $c) use ($needle): \Illuminate\Support\Collection {
            if (!$needle) return $c;
            return $c->filter(fn (array $l) => str_contains(
                strtolower(($l['actor'] ?? '') . ($l['action'] ?? '') . ($l['detail'] ?? '')),
                $needle
            ))->values();
        };

        $orders   = $filterFn($this->orderLogEntries());
        $products = $filterFn($this->productLogEntries());
        $users    = $filterFn($this->userLogEntries());

        // Choose the active dataset
        $active = match ($tab) {
            'orders'   => $orders,
            'products' => $products,
            'users'    => $users,
            default    => $orders->concat($products)->concat($users)->sortByDesc('time')->values(),
        };

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $active->slice(($page - 1) * $perPage, $perPage)->values(),
            $active->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.settings.audit-logs', [
            'logs'          => $paginator,
            'search'        => $search,
            'tab'           => $tab,
            'counts'        => [
                'all'      => $orders->count() + $products->count() + $users->count(),
                'orders'   => $orders->count(),
                'products' => $products->count(),
                'users'    => $users->count(),
            ],
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function orderLogEntries(): \Illuminate\Support\Collection
    {
        return Order::with('customer:id,name', 'seller:id,name')
            ->orderBy('createdAt', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Order $o): array => [
                'time'   => $o->createdAt,
                'actor'  => $o->customer->name ?? 'Guest',
                'action' => 'Placed Order',
                'detail' => 'Order #' . $o->id . ' — ₱' . number_format((float) $o->totalAmount, 2) . ' (' . $o->status . ')',
                'type'   => 'order',
            ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function productLogEntries(): \Illuminate\Support\Collection
    {
        return Product::with('seller:id,name')
            ->orderBy('createdAt', 'desc')
            ->limit(30)
            ->get()
            ->map(fn (Product $p): array => [
                'time'   => $p->createdAt,
                'actor'  => $p->seller->name ?? 'Unknown Seller',
                'action' => 'Listed Product',
                'detail' => '"' . $p->name . '" — Status: ' . $p->status,
                'type'   => 'product',
            ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function userLogEntries(): \Illuminate\Support\Collection
    {
        return User::orderBy('createdAt', 'desc')
            ->limit(20)
            ->get()
            ->map(fn (User $u): array => [
                'time'   => $u->createdAt,
                'actor'  => $u->name,
                'action' => 'Registered Account',
                'detail' => $u->email . ' — Role: ' . $u->role,
                'type'   => 'user',
            ]);
    }

    // ── Platform Info ─────────────────────────────────────────────────────────

    public function platform()
    {
        $info = [
            'app' => [
                'name'        => config('app.name'),
                'version'     => app()->version(),
                'environment' => app()->environment(),
                'debug'       => config('app.debug') ? 'Enabled' : 'Disabled',
                'url'         => config('app.url'),
                'timezone'    => config('app.timezone'),
                'locale'      => config('app.locale'),
            ],
            'php' => [
                'version'    => PHP_VERSION,
                'os'         => PHP_OS,
                'extensions' => implode(', ', array_slice(get_loaded_extensions(), 0, 12)) . '...',
                'memory_limit'=> ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time') . 's',
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ],
            'database' => [
                'driver'  => config('database.default'),
                'host'    => config('database.connections.' . config('database.default') . '.host', 'N/A'),
                'database'=> config('database.connections.' . config('database.default') . '.database', 'N/A'),
                'version' => $this->getDbVersion(),
            ],
            'stats' => [
                'total_users'    => User::count(),
                'total_orders'   => Order::count(),
                'total_products' => Product::count(),
                'total_revenue'  => '₱' . number_format(Order::whereNotIn('status', ['Cancelled'])->sum('totalAmount'), 2),
                'db_size'        => $this->getDbSize(),
            ],
        ];

        return view('admin.settings.platform', compact('info'));
    }

    private function getDbVersion(): string
    {
        try {
            return DB::select('SELECT VERSION() as v')[0]->v ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getDbSize(): string
    {
        try {
            $mb = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                              FROM information_schema.tables
                              WHERE table_schema = ?", [config('database.connections.' . config('database.default') . '.database')])[0]->size ?? 0;
            return "{$mb} MB";
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}
