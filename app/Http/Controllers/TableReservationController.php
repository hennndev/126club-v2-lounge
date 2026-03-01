<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = TableReservation::with(['table.area', 'customer.profile', 'customer.customerUser', 'tableSession.billing']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('profile', function ($profileQuery) use ($search) {
                                $profileQuery->where('phone', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('table', function ($tableQuery) use ($search) {
                        $tableQuery->where('table_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->whereHas('table.area', function ($areaQuery) use ($request) {
                $areaQuery->where('id', $request->category);
            });
        }

        $tab = $request->get('tab', 'all');

        if ($tab === 'active') {
            $query->whereIn('status', ['confirmed', 'checked_in']);
        } elseif ($tab === 'history') {
            $query->whereIn('status', ['completed', 'cancelled', 'rejected']);

            if ($request->filled('date_from')) {
                $query->where('reservation_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('reservation_date', '<=', $request->date_to);
            }
        }

        $bookings = $query->latest('reservation_date')->latest('reservation_time')->get();

        $totalBookings = TableReservation::count();
        $pendingBookings = TableReservation::where('status', 'pending')->count();
        $confirmedBookings = TableReservation::where('status', 'confirmed')->count();
        $checkedInBookings = TableReservation::where('status', 'checked_in')->count();

        $tables = Tabel::with('area')->where('is_active', true)->orderBy('table_number')->get();
        $customers = User::whereHas('customerUser')->with('profile')->orderBy('name')->get();
        $areas = \App\Models\Area::where('is_active', true)->orderBy('sort_order')->get();

        $today = now()->toDateString();
        $todayBookedTableIds = TableReservation::whereIn('status', ['confirmed'])
            ->where('reservation_date', $today)
            ->pluck('table_id')
            ->unique();
        $todayCheckedInTableIds = TableReservation::where('status', 'checked_in')
            ->where('reservation_date', $today)
            ->pluck('table_id')
            ->unique();

        $availableTablesCount = $tables->count() - $todayBookedTableIds->count() - $todayCheckedInTableIds->count();
        $bookedTablesCount = $todayBookedTableIds->count();
        $checkedInTablesCount = $todayCheckedInTableIds->count();

        $todayActiveBookingsByTable = TableReservation::with(['customer.profile', 'customer.customerUser', 'tableSession.billing'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('reservation_date', $today)
            ->get()
            ->keyBy('table_id');

        $todayPendingBookings = TableReservation::with(['table.area', 'customer.profile', 'customer.customerUser'])
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->get();

        // History stats
        $historyTotalCount = TableReservation::whereIn('status', ['completed', 'cancelled', 'rejected'])->count();
        $historyCompletedCount = TableReservation::where('status', 'completed')->count();
        $historyTotalRevenue = \App\Models\Billing::whereHas('tableSession', function ($q) {
            $q->whereHas('reservation', function ($q2) {
                $q2->where('status', 'completed');
            });
        })->sum('grand_total');
        $historyAvgSpending = $historyCompletedCount > 0
            ? $historyTotalRevenue / $historyCompletedCount
            : 0;

        return view('bookings.index', compact(
            'bookings',
            'totalBookings',
            'pendingBookings',
            'confirmedBookings',
            'checkedInBookings',
            'tables',
            'customers',
            'areas',
            'tab',
            'todayBookedTableIds',
            'todayCheckedInTableIds',
            'todayActiveBookingsByTable',
            'todayPendingBookings',
            'availableTablesCount',
            'bookedTablesCount',
            'checkedInTablesCount',
            'historyTotalCount',
            'historyCompletedCount',
            'historyTotalRevenue',
            'historyAvgSpending'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_id' => 'required|exists:users,id',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled,rejected',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            // Check if table is already reserved by another customer
            if (in_array($validated['status'], ['confirmed', 'checked_in'])) {
                $existingBooking = TableReservation::where('table_id', $validated['table_id'])
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where('reservation_date', $validated['reservation_date'])
                    ->first();

                if ($existingBooking) {
                    $table = Tabel::with('area')->find($validated['table_id']);
                    $customerName = $existingBooking->customer->name ?? 'Customer lain';

                    return back()->withErrors([
                        'table_id' => "Meja {$table->area->name} - Nomor {$table->table_number} sudah direservasi oleh {$customerName} pada tanggal yang sama.",
                    ])->withInput();
                }
            }

            // Generate unique booking code
            $lastBooking = TableReservation::latest('id')->first();
            $validated['booking_code'] = $lastBooking ? $lastBooking->booking_code + 1 : 1;

            $booking = TableReservation::create($validated);

            // Update table status based on booking status
            if ($validated['status'] === 'confirmed' || $validated['status'] === 'checked_in') {
                Tabel::where('id', $validated['table_id'])->update(['status' => 'reserved']);
            }

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menambahkan booking: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, TableReservation $booking)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_id' => 'required|exists:users,id',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled,rejected',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            // Check if trying to change from pending to confirmed/checked_in
            if ($booking->status === 'pending' && in_array($validated['status'], ['confirmed', 'checked_in'])) {
                // Check if table is already reserved by another customer
                $existingBooking = TableReservation::where('table_id', $validated['table_id'])
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where('reservation_date', $validated['reservation_date'])
                    ->where('id', '!=', $booking->id)
                    ->first();

                if ($existingBooking) {
                    $table = Tabel::with('area')->find($validated['table_id']);
                    $customerName = $existingBooking->customer->name ?? 'Customer lain';

                    return back()->withErrors([
                        'status' => "Tidak dapat mengkonfirmasi booking. Meja {$table->area->name} - Nomor {$table->table_number} sudah direservasi oleh {$customerName} pada tanggal yang sama. Silakan ubah status ke 'Cancelled' dan tambahkan catatan untuk customer.",
                    ])->withInput();
                }
            }

            $oldTableId = $booking->table_id;
            $oldStatus = $booking->status;

            $booking->update($validated);

            // Update old table status to available if table changed
            if ($oldTableId != $validated['table_id']) {
                Tabel::where('id', $oldTableId)->update(['status' => 'available']);
            }

            // Update new table status based on booking status
            if ($validated['status'] === 'confirmed' || $validated['status'] === 'checked_in') {
                Tabel::where('id', $validated['table_id'])->update(['status' => 'reserved']);
            } elseif ($validated['status'] === 'completed' || $validated['status'] === 'cancelled') {
                Tabel::where('id', $validated['table_id'])->update(['status' => 'available']);
            }

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking berhasil diupdate');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengupdate booking: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(TableReservation $booking)
    {
        try {
            $booking->delete();

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus booking: '.$e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, TableReservation $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled,rejected',
        ]);

        try {
            // Check if trying to change from pending to confirmed/checked_in
            if ($booking->status === 'pending' && in_array($validated['status'], ['confirmed', 'checked_in'])) {
                // Check if table is already reserved by another customer
                $existingBooking = TableReservation::where('table_id', $booking->table_id)
                    ->whereIn('status', ['confirmed', 'checked_in'])
                    ->where('reservation_date', $booking->reservation_date)
                    ->where('id', '!=', $booking->id)
                    ->first();

                if ($existingBooking) {
                    $table = Tabel::with('area')->find($booking->table_id);
                    $customerName = $existingBooking->customer->name ?? 'Customer lain';

                    return back()->withErrors([
                        'status' => "Tidak dapat mengkonfirmasi booking. Meja {$table->area->name} - Nomor {$table->table_number} sudah direservasi oleh {$customerName} pada tanggal yang sama. Silakan ubah status ke 'Cancelled' dan tambahkan catatan untuk customer.",
                    ]);
                }
            }

            DB::transaction(function () use ($booking, $validated) {
                $booking->update(['status' => $validated['status']]);

                // Update table status based on booking status
                $table = Tabel::find($booking->table_id);
                if ($table) {
                    if ($validated['status'] === 'confirmed' || $validated['status'] === 'checked_in') {
                        $table->update(['status' => 'reserved']);
                    } elseif (in_array($validated['status'], ['completed', 'cancelled', 'rejected'])) {
                        $table->update(['status' => 'available']);
                    }
                }

                // Create TableSession + Billing when checking in manually
                if ($validated['status'] === 'checked_in') {
                    $existingSession = $booking->tableSession;

                    if (! $existingSession) {
                        $session = TableSession::create([
                            'table_reservation_id' => $booking->id,
                            'table_id' => $booking->table_id,
                            'customer_id' => $booking->customer_id,
                            'session_code' => 'SES-'.strtoupper(Str::random(10)),
                            'checked_in_at' => now(),
                            'status' => 'active',
                        ]);

                        $minimumCharge = $booking->table?->minimum_charge ?? 0;
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

                        $session->update(['billing_id' => $billing->id]);
                    }
                }

                // Close TableSession when booking is completed
                if ($validated['status'] === 'completed') {
                    $session = $booking->tableSession;
                    if ($session && $session->status === 'active') {
                        $session->update([
                            'checked_out_at' => now(),
                            'status' => 'completed',
                        ]);
                    }
                }
            });

            $statusMessages = [
                'confirmed' => 'Booking berhasil dikonfirmasi',
                'checked_in' => 'Customer berhasil check-in',
                'completed' => 'Booking berhasil diselesaikan',
                'cancelled' => 'Booking berhasil dibatalkan',
                'rejected' => 'Booking berhasil ditolak',
            ];

            $message = $statusMessages[$validated['status']] ?? 'Status booking berhasil diupdate';

            return redirect()->route('admin.bookings.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengupdate status: '.$e->getMessage()]);
        }
    }

    public function closeBilling(Request $request, TableReservation $booking)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,kredit,debit',
        ]);

        $session = $booking->load('tableSession.billing.tableSession.orders.items')->tableSession;

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Table session tidak ditemukan.'], 404);
        }

        $billing = $session->billing;

        if (! $billing) {
            return response()->json(['success' => false, 'message' => 'Billing tidak ditemukan.'], 404);
        }

        if ($billing->billing_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Billing sudah ditutup.'], 422);
        }

        try {
            DB::transaction(function () use ($booking, $session, $billing, $validated) {
                // Recalculate final totals
                $ordersTotal = $session->orders()->sum('total');
                $subtotal = $billing->minimum_charge + $ordersTotal;
                $tax = $subtotal * ($billing->tax_percentage / 100);
                $grandTotal = $subtotal + $tax - $billing->discount_amount;
                $transactionCode = 'TRX-'.now()->timestamp.rand(100, 999);

                $billing->update([
                    'orders_total' => $ordersTotal,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $grandTotal,
                    'billing_status' => 'paid',
                    'transaction_code' => $transactionCode,
                    'payment_method' => $validated['payment_method'],
                ]);

                $session->update([
                    'checked_out_at' => now(),
                    'status' => 'completed',
                ]);

                $booking->update(['status' => 'completed']);

                $table = Tabel::find($booking->table_id);
                $table?->update(['status' => 'available']);
            });

            $billing->refresh();
            $session->load('orders.items');

            // Build items list from all orders in the session
            $allItems = $session->orders->flatMap(fn ($order) => $order->items)->groupBy('item_name')->map(function ($group) {
                $first = $group->first();

                return [
                    'name' => $first->item_name,
                    'qty' => $group->sum('quantity'),
                    'price' => (float) $first->price,
                    'subtotal' => $group->sum('subtotal'),
                ];
            })->values();

            $customerName = $booking->customer->profile->name ?? $booking->customer->customerUser->name ?? $booking->customer->name ?? '-';

            return response()->json([
                'success' => true,
                'message' => 'Billing berhasil ditutup',
                'receipt' => [
                    'transaction_code' => $billing->transaction_code,
                    'date' => now()->format('d M Y H:i'),
                    'cashier' => auth()->user()->name,
                    'customer_name' => $customerName,
                    'table' => $booking->table?->table_number ?? '-',
                    'items' => $allItems,
                    'minimum_charge' => (float) $billing->minimum_charge,
                    'orders_total' => (float) $billing->orders_total,
                    'subtotal' => (float) $billing->subtotal,
                    'tax' => (float) $billing->tax,
                    'tax_percentage' => (float) $billing->tax_percentage,
                    'discount_amount' => (float) $billing->discount_amount,
                    'grand_total' => (float) $billing->grand_total,
                    'payment_method' => strtoupper($billing->payment_method),
                ],
                'receipt_url' => route('admin.bookings.receipt', $booking->id),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menutup billing: '.$e->getMessage()], 500);
        }
    }

    public function receipt(TableReservation $booking)
    {
        $booking->load([
            'table.area',
            'customer.profile',
            'customer.customerUser',
            'tableSession.billing',
            'tableSession.orders.items',
        ]);

        $session = $booking->tableSession;
        $billing = $session?->billing;

        $allItems = $session?->orders->flatMap(fn ($order) => $order->items)->groupBy('item_name')->map(function ($group) {
            $first = $group->first();

            return [
                'name' => $first->item_name,
                'qty' => $group->sum('quantity'),
                'price' => (float) $first->price,
                'subtotal' => $group->sum('subtotal'),
            ];
        })->values() ?? collect();

        $customerName = $booking->customer->profile->name ?? $booking->customer->customerUser->name ?? $booking->customer->name ?? '-';

        return view('bookings.receipt', compact('booking', 'billing', 'allItems', 'customerName'));
    }
}
