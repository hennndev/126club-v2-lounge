<?php

namespace App\Http\Controllers;

use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccurateSyncController extends Controller
{
    protected $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Tampilkan halaman sync
     */
    public function index()
    {
        $syncIntervalHours = config('accurate.sync_interval_hours', 2);
        $syncIntervalMinutes = config('accurate.sync_interval_minutes', null);
        $autoSyncEnabled = config('accurate.auto_sync_enabled', true);

        // Check if sync is currently running
        $isSyncRunning = Cache::has('accurate_sync_running');

        return view('accurate.sync', compact('syncIntervalHours', 'syncIntervalMinutes', 'autoSyncEnabled', 'isSyncRunning'));
    }

    /**
     * Manual sync via button
     */
    public function syncItems(Request $request)
    {
        if (Cache::has('accurate_sync_running')) {
            return response()->json([
                'success' => false,
                'message' => 'Sync sedang berjalan. Mohon tunggu hingga selesai.',
            ], 423);
        }

        try {
            Cache::put('accurate_sync_running', true, now()->addMinutes(30));
            Artisan::call('accurate:sync-items', ['--force' => true]);
            $output = Artisan::output();
            Cache::forget('accurate_sync_running');

            return response()->json([
                'success' => true,
                'message' => 'Sync items berhasil diselesaikan!',
                'output' => $output,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            // Clear flag jika error
            Cache::forget('accurate_sync_running');

            Log::error('Manual sync items failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status
     */
    public function status()
    {
        $isSyncRunning = Cache::has('accurate_sync_running');
        $lastSync = Cache::get('accurate_last_sync_items');

        return response()->json([
            'is_running' => $isSyncRunning,
            'last_sync' => $lastSync,
            'auto_sync_enabled' => config('accurate.auto_sync_enabled', true),
            'sync_interval_hours' => config('accurate.sync_interval_hours', 2),
            'sync_interval_minutes' => config('accurate.sync_interval_minutes', null),
        ]);
    }

    /**
     * Update sync interval
     */
    public function updateInterval(Request $request)
    {
        $request->validate([
            'interval' => 'required|integer|min:1',
            'unit' => 'required|in:minutes,hours',
        ]);

        $interval = $request->input('interval');
        $unit = $request->input('unit');

        // Update .env file
        if ($unit === 'minutes') {
            $this->updateEnvFile('ACCURATE_SYNC_INTERVAL_MINUTES', $interval);
            $this->updateEnvFile('ACCURATE_SYNC_INTERVAL_HOURS', '');
            $message = "Interval sync berhasil diubah menjadi {$interval} menit";
        } else {
            $this->updateEnvFile('ACCURATE_SYNC_INTERVAL_HOURS', $interval);
            $this->updateEnvFile('ACCURATE_SYNC_INTERVAL_MINUTES', '');
            $message = "Interval sync berhasil diubah menjadi {$interval} jam";
        }

        // Clear config cache
        Artisan::call('config:clear');

        return response()->json([
            'success' => true,
            'message' => $message,
            'interval' => $interval,
            'unit' => $unit,
        ]);
    }

    /**
     * Toggle auto sync
     */
    public function toggleAutoSync(Request $request)
    {
        $enabled = $request->input('enabled', true);

        $this->updateEnvFile('ACCURATE_AUTO_SYNC_ENABLED', $enabled ? 'true' : 'false');

        Artisan::call('config:clear');

        return response()->json([
            'success' => true,
            'message' => 'Auto sync '.($enabled ? 'diaktifkan' : 'dinonaktifkan'),
            'enabled' => $enabled,
        ]);
    }

    /**
     * Manual sync BOM via button
     */
    public function syncBom(Request $request)
    {
        if (Cache::has('accurate_bom_sync_running')) {
            return response()->json([
                'success' => false,
                'message' => 'Sync BOM sedang berjalan. Mohon tunggu hingga selesai.',
            ], 423);
        }

        try {
            Cache::put('accurate_bom_sync_running', true, now()->addMinutes(30));
            Artisan::call('accurate:sync-bom', ['--force' => true]);
            $output = Artisan::output();
            Cache::forget('accurate_bom_sync_running');

            return response()->json([
                'success' => true,
                'message' => 'Sync BOM berhasil diselesaikan!',
                'output' => $output,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Cache::forget('accurate_bom_sync_running');

            Log::error('Manual sync BOM failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync BOM gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to update .env file
     */
    protected function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            // Check if key exists
            if (preg_match("/^{$key}=(.*)$/m", $content)) {
                // Update existing key
                $content = preg_replace(
                    "/^{$key}=(.*)$/m",
                    "{$key}={$value}",
                    $content
                );
            } else {
                // Add new key
                $content .= "\n{$key}={$value}\n";
            }

            file_put_contents($path, $content);
        }
    }
}
