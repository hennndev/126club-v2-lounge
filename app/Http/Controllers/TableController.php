<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Billing;
use App\Models\Tabel;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableController extends Controller
{
    // HALAMAN TABLE MANAGEMENT
    public function index(Request $request)
    {
        $query = Tabel::with('area');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('table_number', 'like', "%{$search}%")
                    ->orWhereHas('area', function ($areaQuery) use ($search) {
                        $areaQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('area_id') && $request->area_id != '') {
            $query->where('area_id', $request->area_id);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $tables = $query->orderBy('area_id')->orderBy('table_number')->get();

        $totalTables = Tabel::count();
        $availableTables = Tabel::where('status', 'available')->where('is_active', true)->count();
        $totalCapacity = Tabel::where('is_active', true)->sum('capacity');

        $areas = Area::where('is_active', true)->orderBy('sort_order')->get();
        $areaStats = Area::where('is_active', true)
            ->withCount(['tables' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->get();

        // Get active reservations for reserved tables
        $reservations = \App\Models\TableReservation::with(['customer.profile', 'customer.customerUser', 'table.area'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereIn('table_id', $tables->pluck('id'))
            ->get()
            ->keyBy('table_id');

        return view('tables.index', compact(
            'tables',
            'totalTables',
            'availableTables',
            'totalCapacity',
            'areas',
            'areaStats',
            'reservations'
        ));
    }

    // CREATE NEW TABLE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'table_number' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'minimum_charge' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,reserved,occupied,maintenance',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['qr_code'] = 'QR-'.strtoupper(Str::random(12));
        $validated['is_active'] = $validated['is_active'] ?? true;
        DB::beginTransaction();
        try {
            Tabel::create($validated);
            DB::commit();

            return redirect()->route('admin.tables.index')
                ->with('success', 'Meja berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => 'Gagal menambahkan meja: '.$e->getMessage()]);
        }
    }

    // UPDATE TABLE
    public function update(Request $request, Tabel $table)
    {
        $hasActiveSession = TableSession::query()
            ->where('table_id', $table->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveSession) {
            return back()->withErrors([
                'error' => 'Meja tidak bisa diedit atau dinonaktifkan karena masih memiliki sesi aktif.',
            ])->withInput();
        }

        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'table_number' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'minimum_charge' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,reserved,occupied,maintenance',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        $validated['is_active'] = $validated['is_active'] ?? false;
        DB::beginTransaction();
        try {
            $table->update($validated);
            DB::commit();

            return redirect()->route('admin.tables.index')
                ->with('success', 'Meja berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => 'Gagal mengupdate meja: '.$e->getMessage()]);
        }
    }

    // DELETE TABLE
    public function destroy(Tabel $table)
    {
        DB::beginTransaction();
        try {
            $table->delete();
            DB::commit();

            return redirect()->route('admin.tables.index')
                ->with('success', 'Meja berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal menghapus meja: '.$e->getMessage()]);
        }
    }

    // HALAMAN ACTIVE TABLES
    public function activeTables(Request $request)
    {
        $query = \App\Models\TableSession::with(['table.area', 'customer.profile', 'reservation', 'billing'])
            ->where('status', 'active');

        if ($request->has('area_id') && $request->area_id != '') {
            $query->whereHas('table', function ($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhereHas('table', function ($tableQuery) use ($search) {
                        $tableQuery->where('table_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sessions = $query->orderBy('checked_in_at', 'desc')->get();

        $areas = Area::where('is_active', true)->orderBy('sort_order')->get();

        $totalActiveSessions = \App\Models\TableSession::where('status', 'active')->count();
        $totalRevenue = \App\Models\Billing::whereHas('tableSession', function ($q) {
            $q->where('status', 'active');
        })->sum('grand_total');

        return view('active-tables.index', compact(
            'sessions',
            'areas',
            'totalActiveSessions',
            'totalRevenue'
        ));
    }

    // UPDATE PAX PADA ACTIVE TABLE
    public function updatePax(Request $request, TableSession $session): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'pax' => 'required|integer|min:1|max:9999',
        ]);

        $session->update(['pax' => $validated['pax']]);

        return response()->json(['success' => true, 'pax' => $session->pax]);
    }

    // HALAMAN TABLE SCANNER
    public function scanner()
    {
        return view('table-scanner.index');
    }

    // SCAN QR MEJA
    public function scanQR(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
        ]);

        try {
            $table = Tabel::with(['area'])
                ->where('qr_code', $validated['qr_code'])
                ->first();

            if (! $table) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code tidak valid atau meja tidak ditemukan',
                ], 404);
            }

            // Get active reservation for this table
            $reservation = \App\Models\TableReservation::with(['customer.profile', 'customer.customerUser'])
                ->where('table_id', $table->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'table' => $table,
                    'reservation' => $reservation,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function generateCheckInQR(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:table_reservations,id',
        ]);

        try {
            $reservation = \App\Models\TableReservation::with(['table', 'customer'])
                ->findOrFail($validated['reservation_id']);

            // Generate or regenerate QR code if expired
            if (! $reservation->check_in_qr_code || ! $reservation->check_in_qr_expires_at || $reservation->check_in_qr_expires_at < now()) {
                $reservation->update([
                    'check_in_qr_code' => 'CHECKIN-'.strtoupper(Str::random(16)),
                    'check_in_qr_expires_at' => now()->addMinutes(5), // QR valid for 5 minutes
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'reservation' => $reservation,
                    'qr_code' => $reservation->check_in_qr_code,
                    'expires_at' => $reservation->check_in_qr_expires_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }

    public function processCheckIn(Request $request)
    {
        $validated = $request->validate([
            'qr_code' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Find reservation by QR code
            $reservation = \App\Models\TableReservation::with(['table', 'customer'])
                ->where('check_in_qr_code', $validated['qr_code'])
                ->first();

            if (! $reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code tidak valid',
                ], 404);
            }

            // Check if QR expired
            if (! $reservation->check_in_qr_expires_at || $reservation->check_in_qr_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code sudah expired. Silakan generate ulang.',
                ], 400);
            }

            // Check if already checked in
            if ($reservation->status === 'checked_in') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer sudah check-in',
                ], 400);
            }

            // Step 1: Create table session first (without billing_id)
            // Assign waiter if the check-in is performed by a Waiter role user
            $waiterId = auth()->user()?->hasRole('Waiter/Server') ? auth()->id() : null;

            $session = \App\Models\TableSession::create([
                'table_reservation_id' => $reservation->id,
                'table_id' => $reservation->table_id,
                'customer_id' => $reservation->customer_id,
                'waiter_id' => $waiterId,
                'session_code' => 'SES-'.strtoupper(Str::random(10)),
                'checked_in_at' => now(),
                'status' => 'active',
            ]);

            // Step 2: Create billing for this session
            $minimumCharge = $reservation->table->minimum_charge ?? 0;
            $billing = Billing::create([
                'table_session_id' => $session->id,
                'minimum_charge' => $minimumCharge,
                'orders_total' => 0,
                'subtotal' => 0,
                'tax' => 0,
                'tax_percentage' => 10.00,
                'discount_amount' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'billing_status' => 'draft',
            ]);

            // Step 3: Update session with billing_id
            $session->update([
                'billing_id' => $billing->id,
            ]);

            // Update reservation status and clear QR code
            $reservation->update([
                'status' => 'checked_in',
                'check_in_qr_code' => null,
                'check_in_qr_expires_at' => null,
            ]);

            // Update table status to occupied
            $reservation->table->update(['status' => 'occupied']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $waiterId ? 'Check-in berhasil! Kamu di-assign sebagai waiter.' : 'Check-in berhasil!',
                'data' => [
                    'session' => $session,
                    'customer' => $reservation->customer->name,
                    'table' => $reservation->table->table_number,
                    'waiter_assigned' => $waiterId !== null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }
    }
}
