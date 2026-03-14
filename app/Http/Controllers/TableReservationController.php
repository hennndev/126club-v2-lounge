<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\CustomerUser;
use App\Models\GeneralSetting;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TableReservationController extends Controller
{
    public function __construct(protected AccurateService $accurateService) {}

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
        } elseif ($tab === 'pending') {
            $query->where('status', 'pending');

            if ($request->filled('date_from')) {
                $query->where('reservation_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('reservation_date', '<=', $request->date_to);
            }
        } elseif ($tab === 'history') {
            $query->with('tableSession.orders.items');
            $query->whereIn('status', ['completed', 'cancelled', 'rejected', 'force_closed']);

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

        // Derive table status counts from the tables themselves (consistent with updateStatus logic)
        $availableTablesCount = $tables->where('status', 'available')->count();
        $bookedTablesCount = $tables->where('status', 'reserved')->count();
        $checkedInTablesCount = $tables->where('status', 'occupied')->count();

        // Earliest upcoming confirmed/checked-in booking per table (today or future)
        $activeBookingsByTable = TableReservation::with(['customer.profile', 'customer.customerUser', 'tableSession.billing', 'tableSession.orders.items'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('reservation_date', '>=', now()->toDateString())
            ->get()
            ->sortBy('reservation_date')
            ->unique('table_id')
            ->keyBy('table_id');

        $activeSessions = TableSession::with([
            'table.area',
            'reservation.customer.profile',
            'reservation.customer.customerUser',
            'billing',
            'waiter.profile',
            'orders.items.inventoryItem',
        ])
            ->where('status', 'active')
            ->orderBy('checked_in_at')
            ->get();

        $activeSessionChargePreviews = $activeSessions->mapWithKeys(function (TableSession $session) {
            $billing = $session->billing;

            return [
                $session->id => $this->calculateSessionBillingTotals(
                    $session,
                    (float) ($billing?->discount_amount ?? 0),
                    (float) ($billing?->minimum_charge ?? 0),
                ),
            ];
        });

        $todayPendingBookings = TableReservation::with(['table.area', 'customer.profile', 'customer.customerUser'])
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->get();

        // Pending tab: identify competing bookings (same table + date, >1 pending)
        $conflictingPendingKeys = TableReservation::where('status', 'pending')
            ->selectRaw('table_id, reservation_date, COUNT(*) as cnt')
            ->groupBy('table_id', 'reservation_date')
            ->having('cnt', '>', 1)
            ->get()
            ->map(fn ($r) => $r->table_id.'_'.($r->reservation_date instanceof \Carbon\Carbon ? $r->reservation_date->toDateString() : $r->reservation_date))
            ->toArray();

        // Pending tab: slots already taken by a confirmed/checked-in booking
        $blockedPendingKeys = TableReservation::whereIn('status', ['confirmed', 'checked_in'])
            ->selectRaw('DISTINCT table_id, reservation_date')
            ->get()
            ->map(fn ($r) => $r->table_id.'_'.($r->reservation_date instanceof \Carbon\Carbon ? $r->reservation_date->toDateString() : $r->reservation_date))
            ->toArray();

        // History stats
        $historyTotalCount = TableReservation::whereIn('status', ['completed', 'cancelled', 'rejected', 'force_closed'])->count();
        $historyCompletedCount = TableReservation::where('status', 'completed')->count();
        $historyForceClosedCount = TableReservation::where('status', 'force_closed')->count();
        $historyTotalRevenue = \App\Models\Billing::whereHas('tableSession', function ($q): void {
            $q->whereHas('reservation', function ($q2): void {
                $q2->whereIn('status', ['completed', 'force_closed']);
            });
        })->sum('grand_total');
        $historyAvgSpending = $historyCompletedCount > 0
            ? $historyTotalRevenue / $historyCompletedCount
            : 0;

        $waiters = User::whereHas('roles', fn ($q) => $q->where('name', 'Waiter/Server'))
            ->with('profile')
            ->orderBy('name')
            ->get();

        // JSON response for waiter mobile scanner search
        if ($request->get('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'reservations' => $bookings->map(fn ($b) => [
                    'id' => $b->id,
                    'status' => $b->status,
                    'customer' => $b->customer ? [
                        'name' => $b->customer->name,
                        'email' => $b->customer->email,
                    ] : null,
                    'table' => $b->table ? [
                        'table_number' => $b->table->table_number,
                    ] : null,
                    'reservation_date' => $b->reservation_date,
                    'reservation_time' => $b->reservation_time,
                    'booking_code' => $b->booking_code,
                ])->values(),
            ]);
        }

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
            'activeBookingsByTable',
            'activeSessions',
            'waiters',
            'todayPendingBookings',
            'conflictingPendingKeys',
            'blockedPendingKeys',
            'availableTablesCount',
            'bookedTablesCount',
            'checkedInTablesCount',
            'activeSessionChargePreviews',
            'historyTotalCount',
            'historyCompletedCount',
            'historyForceClosedCount',
            'historyTotalRevenue',
            'historyAvgSpending'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_id' => 'required|exists:users,id',
            'booking_name' => 'nullable|string|max:255',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'note' => 'nullable|string|max:1000',
        ]);

        // New bookings always start as pending — admin must confirm explicitly
        $validated['status'] = 'pending';

        try {
            // Generate unique booking code
            $lastBooking = TableReservation::latest('id')->first();
            $validated['booking_code'] = $lastBooking ? $lastBooking->booking_code + 1 : 1;

            TableReservation::create($validated);

            return redirect()->route('admin.bookings.index')
                ->with('success', 'Booking berhasil ditambahkan. Status: Pending — silakan konfirmasi setelah diverifikasi.');
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
            'booking_name' => 'nullable|string|max:255',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'status' => 'required|in:pending,confirmed,checked_in,completed,cancelled,rejected',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            // Check for conflicts whenever confirming or checking in
            if (in_array($validated['status'], ['confirmed', 'checked_in'])) {
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
            if ($validated['status'] === 'confirmed') {
                Tabel::where('id', $validated['table_id'])->update(['status' => 'reserved']);
            } elseif ($validated['status'] === 'checked_in') {
                Tabel::where('id', $validated['table_id'])->update(['status' => 'occupied']);
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
            DB::transaction(function () use ($booking, $validated) {
                // Check for conflicts inside the transaction with a row-level lock
                // to prevent race conditions when multiple admins confirm simultaneously
                if (in_array($validated['status'], ['confirmed', 'checked_in'])) {
                    $existingBooking = TableReservation::where('table_id', $booking->table_id)
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('reservation_date', $booking->reservation_date)
                        ->where('id', '!=', $booking->id)
                        ->lockForUpdate()
                        ->first();

                    if ($existingBooking) {
                        $table = Tabel::with('area')->find($booking->table_id);
                        $customerName = $existingBooking->customer->name ?? 'Customer lain';

                        throw new \Exception("Tidak dapat mengkonfirmasi booking. Meja {$table->area->name} - Nomor {$table->table_number} sudah direservasi oleh {$customerName} pada tanggal yang sama.");
                    }
                }

                $booking->update(['status' => $validated['status']]);

                // Update table status based on booking status
                $table = Tabel::find($booking->table_id);
                if ($table) {
                    if ($validated['status'] === 'confirmed') {
                        $table->update(['status' => 'reserved']);
                    } elseif ($validated['status'] === 'checked_in') {
                        $table->update(['status' => 'occupied']);
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
                            'tax_percentage' => 0,
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
            'payment_mode' => 'required|in:normal,split',
            'payment_method' => 'required_if:payment_mode,normal|nullable|in:cash,kredit,debit',
            'split_cash_amount' => 'required_if:payment_mode,split|nullable|numeric|min:0',
            'split_debit_amount' => 'required_if:payment_mode,split|nullable|numeric|min:0',
        ]);

        $session = $booking->load('tableSession.billing.tableSession.orders.items')->tableSession;

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Table session tidak ditemukan.'], 404);
        }

        $billing = $session->billing;

        if (! $billing) {
            return response()->json(['success' => false, 'message' => 'Billing tidak ditemukan.'], 404);
        }

        if ($this->hasIncompleteTransactionChecker($session)) {
            return response()->json([
                'success' => false,
                'message' => 'Billing tidak bisa ditutup karena masih ada item di Transaction Checker yang belum selesai.',
            ], 422);
        }

        if ($billing->billing_status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Billing sudah ditutup.'], 422);
        }

        try {
            DB::transaction(function () use ($booking, $session, $billing, $validated) {
                $session->loadMissing('orders.items.inventoryItem');

                $totals = $this->calculateSessionBillingTotals(
                    $session,
                    (float) $billing->discount_amount,
                    (float) $billing->minimum_charge,
                );

                $transactionCode = 'TRX-'.now()->timestamp.rand(100, 999);

                $paymentMode = $validated['payment_mode'];
                $paymentMethod = $paymentMode === 'split'
                    ? null
                    : $validated['payment_method'];

                $splitCashAmount = null;
                $splitDebitAmount = null;

                if ($paymentMode === 'split') {
                    $splitCashAmount = (float) $validated['split_cash_amount'];
                    $splitDebitAmount = (float) $validated['split_debit_amount'];
                    $splitTotal = round($splitCashAmount + $splitDebitAmount, 2);

                    if (abs($splitTotal - (float) $totals['grand_total']) > 0.01) {
                        throw ValidationException::withMessages([
                            'split_total' => 'Total pembayaran split (cash + debit) harus sama dengan grand total.',
                        ]);
                    }
                }

                $billing->update([
                    'orders_total' => (float) $totals['orders_total'],
                    'subtotal' => (float) $totals['subtotal'],
                    'tax_percentage' => (float) $totals['tax_percentage'],
                    'tax' => (float) $totals['tax'],
                    'service_charge_percentage' => (float) $totals['service_charge_percentage'],
                    'service_charge' => (float) $totals['service_charge'],
                    'grand_total' => (float) $totals['grand_total'],
                    'paid_amount' => (float) $totals['grand_total'],
                    'billing_status' => 'paid',
                    'transaction_code' => $transactionCode,
                    'payment_method' => $paymentMethod,
                    'payment_mode' => $paymentMode,
                    'split_cash_amount' => $splitCashAmount,
                    'split_debit_amount' => $splitDebitAmount,
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

            // Push to Accurate: Sales Order + Sales Invoice (non-blocking)
            $this->pushBillingToAccurate($booking, $session, $billing);

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
                    'service_charge' => (float) $billing->service_charge,
                    'service_charge_percentage' => (float) $billing->service_charge_percentage,
                    'discount_amount' => (float) $billing->discount_amount,
                    'grand_total' => (float) $billing->grand_total,
                    'payment_mode' => strtoupper($billing->payment_mode ?? 'NORMAL'),
                    'payment_method' => strtoupper($billing->payment_method ?? ($billing->payment_mode === 'split' ? 'split' : '-')),
                    'split_cash_amount' => (float) ($billing->split_cash_amount ?? 0),
                    'split_debit_amount' => (float) ($billing->split_debit_amount ?? 0),
                ],
                'receipt_url' => route('admin.bookings.receipt', $booking->id),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Data pembayaran tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menutup billing: '.$e->getMessage()], 500);
        }
    }

    /**
     * @return array<string, float>
     */
    protected function calculateSessionBillingTotals(TableSession $session, float $discountAmount, float $minimumCharge): array
    {
        $settings = GeneralSetting::instance();
        $orders = $session->orders
            ->where('status', '!=', 'cancelled')
            ->values();

        $ordersTotal = (float) $orders->sum(fn ($order) => (float) ($order->total ?? 0));
        $subtotal = max($minimumCharge, $ordersTotal);
        $discountAmount = min(max($discountAmount, 0), $subtotal);
        $subtotalAfterDiscount = max($subtotal - $discountAmount, 0);

        $bases = $this->resolveSessionChargeableBases($orders);
        $discountRatio = $ordersTotal > 0 ? min(max($discountAmount / $ordersTotal, 0), 1) : 0;

        $serviceChargeBaseAfterDiscount = max($bases['service_charge_base'] * (1 - $discountRatio), 0);
        $taxBaseAfterDiscount = max($bases['tax_base'] * (1 - $discountRatio), 0);
        $taxAndServiceBaseAfterDiscount = max($bases['tax_and_service_base'] * (1 - $discountRatio), 0);

        $serviceCharge = round($serviceChargeBaseAfterDiscount * (((float) $settings->service_charge_percentage) / 100), 2);
        $serviceChargeTaxableAmount = round($taxAndServiceBaseAfterDiscount * (((float) $settings->service_charge_percentage) / 100), 2);
        $tax = round(($taxBaseAfterDiscount + $serviceChargeTaxableAmount) * (((float) $settings->tax_percentage) / 100), 2);

        return [
            'orders_total' => $ordersTotal,
            'minimum_charge' => $minimumCharge,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'service_charge_percentage' => (float) $settings->service_charge_percentage,
            'service_charge' => $serviceCharge,
            'tax_percentage' => (float) $settings->tax_percentage,
            'tax' => $tax,
            'grand_total' => $subtotalAfterDiscount + $serviceCharge + $tax,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $orders
     * @return array<string, float>
     */
    protected function resolveSessionChargeableBases(Collection $orders): array
    {
        $serviceChargeBase = 0;
        $taxBase = 0;
        $taxAndServiceBase = 0;

        foreach ($orders as $order) {
            $orderItems = $order->items->where('status', '!=', 'cancelled')->values();
            $orderNetTotal = (float) ($order->total ?? 0);

            if ($orderItems->isEmpty()) {
                $serviceChargeBase += max($orderNetTotal, 0);
                $taxBase += max($orderNetTotal, 0);
                $taxAndServiceBase += max($orderNetTotal, 0);

                continue;
            }

            $itemsSubtotal = (float) $orderItems->sum(fn ($item) => (float) ($item->subtotal ?? 0));
            $ratio = $itemsSubtotal > 0 ? max($orderNetTotal, 0) / $itemsSubtotal : 0;

            foreach ($orderItems as $orderItem) {
                $itemNetSubtotal = (float) ($orderItem->subtotal ?? 0) * $ratio;
                $includeTax = (bool) ($orderItem->inventoryItem?->include_tax ?? true);
                $includeServiceCharge = (bool) ($orderItem->inventoryItem?->include_service_charge ?? true);

                if ($includeServiceCharge) {
                    $serviceChargeBase += $itemNetSubtotal;
                }

                if ($includeTax) {
                    $taxBase += $itemNetSubtotal;
                }

                if ($includeTax && $includeServiceCharge) {
                    $taxAndServiceBase += $itemNetSubtotal;
                }
            }
        }

        return [
            'service_charge_base' => $serviceChargeBase,
            'tax_base' => $taxBase,
            'tax_and_service_base' => $taxAndServiceBase,
        ];
    }

    /**
     * Push a closed billing to Accurate as Sales Order + Sales Invoice.
     * All orders in the session are consolidated into a single SO and invoice.
     * Failures are logged but do not interrupt the close-billing response.
     */
    protected function pushBillingToAccurate(TableReservation $booking, $session, $billing): void
    {
        try {
            $customerUser = CustomerUser::where('user_id', $booking->customer_id)->first();
            $customerNo = $customerUser?->customer_code;

            if (! $customerNo) {
                Log::warning('Accurate Billing Sync: customerNo not found, skipping', [
                    'booking_id' => $booking->id,
                ]);

                return;
            }

            $transDate = now()->format('d/m/Y');
            $reference = $billing->transaction_code;

            // Consolidate all order items across all orders in the session
            $session->loadMissing('orders.items.inventoryItem');

            $detailItem = $session->orders
                ->flatMap(fn ($order) => $order->items)
                ->groupBy('inventory_item_id')
                ->map(function ($group) {
                    $first = $group->first();

                    return [
                        'itemNo' => $first->inventoryItem?->code ?? $first->item_code,
                        'quantity' => $group->sum('quantity'),
                        'unitPrice' => (float) $first->price,
                        'discountPercent' => 0,
                    ];
                })
                ->values()
                ->toArray();

            if (empty($detailItem)) {
                Log::warning('Accurate Billing Sync: no items found, skipping', [
                    'booking_id' => $booking->id,
                ]);

                return;
            }

            // 1. Save Sales Order
            $soPayload = [
                'customerNo' => $customerNo,
                'transDate' => $transDate,
                'number' => $reference,
                'memo' => 'Booking POS — '.$reference,
                'detailItem' => $detailItem,
            ];

            $soResult = $this->accurateService->saveSalesOrder($soPayload);
            $soNumber = $soResult['r']['number'] ?? $soResult['d']['number'] ?? null;

            // 2. Save Sales Invoice
            $invPayload = [
                'customerNo' => $customerNo,
                'transDate' => $transDate,
                'memo' => 'Booking POS — '.$reference,
                'detailItem' => $detailItem,
            ];

            if ($soNumber) {
                $invPayload['salesOrderNumber'] = $soNumber;
            }

            $invResult = $this->accurateService->saveSalesInvoice($invPayload);
            $invNumber = $invResult['r']['number'] ?? $invResult['d']['number'] ?? null;

            // 3. Persist Accurate numbers on the billing record
            $billing->update([
                'accurate_so_number' => $soNumber,
                'accurate_inv_number' => $invNumber,
            ]);

            Log::info('Accurate Billing Sync: OK', [
                'booking_id' => $booking->id,
                'so_number' => $soNumber,
                'inv_number' => $invNumber,
            ]);
        } catch (\Exception $e) {
            Log::error('Accurate Billing Sync: FAILED', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function assignWaiter(Request $request, TableReservation $booking)
    {
        $validated = $request->validate([
            'waiter_id' => 'nullable|exists:users,id',
        ]);

        $session = $booking->tableSession;

        if (! $session) {
            return back()->withErrors(['error' => 'Sesi aktif tidak ditemukan untuk booking ini.']);
        }

        $previousWaiterId = $session->waiter_id;
        $newWaiterId = $validated['waiter_id'] ?? null;

        $session->update(['waiter_id' => $newWaiterId]);

        // Send notification to newly assigned waiter (not when unassigning)
        if ($newWaiterId && $newWaiterId !== $previousWaiterId) {
            $waiter = User::find($newWaiterId);
            $waiter?->notify(new \App\Notifications\WaiterAssignedNotification($booking->load(['table.area', 'customer.profile', 'customer.customerUser'])));
        }

        $waiterName = $newWaiterId
            ? (User::find($newWaiterId)?->profile?->name ?? User::find($newWaiterId)?->name ?? '-')
            : 'tidak ada';

        return back()->with('success', "Waiter berhasil di-assign: {$waiterName}.");
    }

    public function receipt(TableReservation $booking)
    {
        $booking->load([
            'table.area',
            'customer.profile',
            'customer.customerUser',
            'tableSession.billing',
            'tableSession.orders.items.inventoryItem',
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

    protected function hasIncompleteTransactionChecker(TableSession $session): bool
    {
        $checkerItems = $session->orders
            ->flatMap(fn ($order) => $order->items)
            ->where('status', '!=', 'cancelled');

        if ($checkerItems->isEmpty()) {
            return false;
        }

        return $checkerItems->where('status', 'served')->count() < $checkerItems->count();
    }
}
