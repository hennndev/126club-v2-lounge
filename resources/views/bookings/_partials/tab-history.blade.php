{{-- HISTORY TAB --}}

{{-- Search & Filters row --}}
<div class="flex items-center gap-3 mb-5">
  <div class="flex-1 relative">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
    <input type="text"
           id="searchInput"
           placeholder="Cari booking (nama, telepon, ID)..."
           class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
  </div>
  <select id="categoryFilter"
          class="px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
    <option value="">Semua Category</option>
    @foreach ($areas as $area)
      <option value="{{ $area->id }}">{{ $area->name }}</option>
    @endforeach
  </select>
  <select id="statusFilter"
          class="px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
    <option value="">Semua Status</option>
    <option value="completed">Completed</option>
    <option value="cancelled">Cancelled</option>
    <option value="rejected">Rejected</option>
    <option value="force_closed">Force Closed</option>
  </select>
</div>

{{-- 4 Stat cards --}}
<div class="grid grid-cols-4 gap-4 mb-5">
  {{-- Total Booking --}}
  <div class="bg-slate-700 rounded-xl px-5 py-4 flex items-center gap-4">
    <div class="w-10 h-10 bg-slate-500 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-5 h-5 text-white"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
    <div>
      <div class="text-2xl font-bold text-white">{{ $historyTotalCount }}</div>
      <div class="text-sm font-semibold text-slate-300">Total Booking</div>
    </div>
  </div>

  {{-- Completed --}}
  <div class="bg-green-800 rounded-xl px-5 py-4 flex items-center gap-4">
    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-5 h-5 text-white"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <div>
      <div class="text-2xl font-bold text-white">{{ $historyCompletedCount }}</div>
      <div class="text-sm font-semibold text-green-200">Completed</div>
    </div>
  </div>

  {{-- Avg Spending --}}
  <div class="bg-blue-800 rounded-xl px-5 py-4 flex items-center gap-4">
    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-5 h-5 text-white"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
    <div>
      <div class="text-lg font-bold text-white">Rp {{ number_format($historyAvgSpending, 0, ',', '.') }}</div>
      <div class="text-sm font-semibold text-blue-200">Avg Spending</div>
    </div>
  </div>

  {{-- Total Revenue --}}
  <div class="bg-amber-800 rounded-xl px-5 py-4 flex items-center gap-4">
    <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-5 h-5 text-white"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <div>
      <div class="text-lg font-bold text-white">Rp {{ number_format($historyTotalRevenue, 0, ',', '.') }}</div>
      <div class="text-sm font-semibold text-amber-200">Total Revenue</div>
    </div>
  </div>
</div>

{{-- Table card --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

  {{-- Date range filter --}}
  <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
    <span class="text-sm font-medium text-gray-500">Filter Tanggal:</span>
    <form method="GET"
          action="{{ route('admin.bookings.index') }}"
          class="flex items-center gap-2">
      <input type="hidden"
             name="tab"
             value="history">
      @if (request('search'))
        <input type="hidden"
               name="search"
               value="{{ request('search') }}">
      @endif
      <input type="date"
             name="date_from"
             value="{{ request('date_from') }}"
             class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white text-gray-700">
      <span class="text-gray-400">–</span>
      <input type="date"
             name="date_to"
             value="{{ request('date_to') }}"
             class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white text-gray-700">
      <button type="submit"
              class="px-3 py-1.5 text-xs font-medium bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition">
        Filter
      </button>
      @if (request('date_from') || request('date_to'))
        <a href="{{ route('admin.bookings.index', ['tab' => 'history']) }}"
           class="px-3 py-1.5 text-xs font-medium text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
          Reset
        </a>
      @endif
    </form>
  </div>

  @if ($bookings->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
      <svg class="w-12 h-12 mb-3 text-gray-300"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="text-sm font-medium">Tidak ada riwayat booking</p>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Customer</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Kontak</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Category</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Table</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Date/Time</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Orders</th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-gray-600">Total Spent</th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-600">Error Message</th>
            <th class="px-5 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100"
               id="bookingTableBody">
          @foreach ($bookings as $booking)
            @php
              $totalSpent = $booking->tableSession?->billing?->grand_total;
              $orderedItems =
                  $booking->tableSession?->orders
                      ?->flatMap(fn($order) => $order->items)
                      ->groupBy('item_name')
                      ->map(
                          fn($group) => [
                              'name' => $group->first()->item_name,
                              'qty' => (int) $group->sum('quantity'),
                          ],
                      )
                      ->values() ?? collect();
              $histAreaName = $booking->table?->area?->name ?? '';
              $histAreaKey = strtolower($histAreaName);
              $histAreaBadge = match (true) {
                  str_contains($histAreaKey, 'room') || str_contains($histAreaKey, 'vip') => 'bg-purple-100 text-purple-700',
                  str_contains($histAreaKey, 'balcony') => 'bg-violet-100 text-violet-700',
                  str_contains($histAreaKey, 'lounge') => 'bg-cyan-100 text-cyan-700',
                  strlen($histAreaName) > 0 => 'bg-gray-100 text-gray-600',
                  default => '',
              };
            @endphp
            <tr class="hover:bg-gray-50 transition-colors booking-row cursor-pointer"
                data-booking-id="{{ $booking->id }}"
                data-status="{{ $booking->status }}"
                data-category="{{ $booking->table?->area_id }}">
              <td class="px-5 py-4 whitespace-nowrap">
                @php
                  $sc = match ($booking->status) {
                      'completed' => ['bg-green-100 text-green-700', 'Completed'],
                      'cancelled' => ['bg-red-100 text-red-700', 'Cancelled'],
                      'rejected' => ['bg-orange-100 text-orange-700', 'Rejected'],
                      'force_closed' => ['bg-amber-100 text-amber-700', 'Force Closed'],
                      default => ['bg-gray-100 text-gray-600', ucfirst($booking->status)],
                  };
                @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $sc[0] }}">
                  @if ($booking->status === 'completed')
                    <svg class="w-3 h-3"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M9 12l2 2 4-4" />
                    </svg>
                  @else
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                  @endif
                  {{ $sc[1] }}
                </span>
              </td>
              <td class="px-5 py-4">
                <div class="text-base font-semibold text-gray-900">{{ $booking->customer->name }}</div>
                @if ($booking->note)
                  <div class="text-sm text-gray-400 mt-0.5">{{ $booking->note }}</div>
                @endif
              </td>
              <td class="px-5 py-4">
                <div class="text-sm text-gray-700">{{ $booking->customer->profile?->phone ?? '-' }}</div>
                <div class="text-sm text-gray-400 mt-0.5">{{ $booking->customer->email }}</div>
              </td>
              <td class="px-5 py-4 whitespace-nowrap">
                @if ($histAreaBadge)
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium {{ $histAreaBadge }}">
                    {{ $histAreaName }}
                  </span>
                @else
                  <span class="text-gray-400 text-sm">-</span>
                @endif
              </td>
              <td class="px-5 py-4">
                @if ($booking->table)
                  <div class="text-base font-semibold text-gray-900">{{ $booking->table->table_number }}</div>
                  <div class="text-sm text-gray-400">{{ $booking->table->capacity }} seats</div>
                @else
                  <span class="text-gray-400 text-sm">No table</span>
                @endif
              </td>
              <td class="px-5 py-4 whitespace-nowrap">
                @if ($booking->reservation_date)
                  <div class="text-base font-medium text-gray-800">
                    {{ $booking->reservation_date->format('d M Y') }}
                  </div>
                  <div class="text-sm text-gray-400 mt-0.5">
                    {{ date('H:i', strtotime($booking->reservation_time)) }}
                  </div>
                @else
                  <span class="text-gray-400 text-sm">-</span>
                @endif
              </td>
              <td class="px-5 py-4">
                @if ($orderedItems->isNotEmpty())
                  <button type="button"
                          onclick="openHistoryBookingOrdersModal({{ $booking->id }})"
                          class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Lihat Orders ({{ $orderedItems->count() }} item)
                  </button>
                @else
                  <span class="text-gray-300 text-sm">-</span>
                @endif
              </td>
              <td class="px-5 py-4 whitespace-nowrap text-right">
                @if ($totalSpent)
                  <span class="text-base font-bold text-gray-900">
                    Rp {{ number_format($totalSpent, 0, ',', '.') }}
                  </span>
                @else
                  <span class="text-gray-300 text-sm">-</span>
                @endif
              </td>
              <td class="px-5 py-4">
                @if ($booking->tableSession?->billing?->error_message)
                  <button type="button"
                          data-error-message="{{ $booking->tableSession->billing->error_message }}"
                          onclick="openHistoryErrorModal(this)"
                          class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition">
                    Lihat Error
                  </button>
                @else
                  <span class="text-gray-300 text-sm">-</span>
                @endif
              </td>
              <td class="px-5 py-4 whitespace-nowrap text-right">
                @if ($booking->tableSession?->billing)
                  @php
                    $billing = $booking->tableSession->billing;
                    $isAccurateMissing = !$billing->accurate_so_number || !$billing->accurate_inv_number;
                  @endphp
                  <div class="inline-flex items-center gap-2">
                    @if ($isAccurateMissing)
                      <form method="POST"
                            action="{{ route('admin.bookings.reSyncAccurate', $booking) }}"
                            class="inline"
                            onsubmit="const button = this.querySelector('[data-resync-accurate-button]'); if (button) { button.disabled = true; button.textContent = 'Sync...'; }">
                        @csrf
                        <button type="submit"
                                data-resync-accurate-button
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-100 text-amber-700 hover:bg-amber-200 transition disabled:opacity-60 disabled:cursor-not-allowed">
                          Re-sync Accurate
                        </button>
                      </form>
                    @endif
                    <form method="POST"
                          action="{{ route('admin.bookings.reprintReceipt', $booking) }}"
                          class="inline"
                          onsubmit="const button = this.querySelector('[data-reprint-button]'); if (button) { button.disabled = true; button.textContent = 'Memproses...'; }">
                      @csrf
                      <button type="submit"
                              data-reprint-button
                              class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        Print Ulang
                      </button>
                    </form>
                  </div>
                @else
                  <span class="text-gray-300 text-sm">-</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if ($bookings instanceof \Illuminate\Pagination\LengthAwarePaginator && $bookings->hasPages())
      <div class="px-5 py-4 border-t border-gray-100">
        <div class="flex items-center justify-between gap-4 text-sm">
          <p class="text-gray-500">
            Menampilkan
            <span class="font-semibold text-gray-700">{{ $bookings->firstItem() }}</span>
            -
            <span class="font-semibold text-gray-700">{{ $bookings->lastItem() }}</span>
            dari
            <span class="font-semibold text-gray-700">{{ $bookings->total() }}</span>
            booking
          </p>

          <div class="flex items-center gap-1.5">
            @if ($bookings->onFirstPage())
              <span class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed">Prev</span>
            @else
              <a href="{{ $bookings->previousPageUrl() }}"
                 class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">Prev</a>
            @endif

            @php
              $historyCurrentPage = (int) $bookings->currentPage();
              $historyLastPage = (int) $bookings->lastPage();
              $historyVisiblePages = collect([1, $historyCurrentPage - 1, $historyCurrentPage, $historyCurrentPage + 1, $historyLastPage])
                  ->filter(fn($page) => $page >= 1 && $page <= $historyLastPage)
                  ->unique()
                  ->sort()
                  ->values();

              $historyPreviousVisiblePage = null;
            @endphp

            @foreach ($historyVisiblePages as $page)
              @if ($historyPreviousVisiblePage !== null && $page - $historyPreviousVisiblePage > 1)
                <span class="pagination-ellipsis inline-flex items-center justify-center w-9 h-9 text-gray-400 select-none">...</span>
              @endif

              @if ($page === $historyCurrentPage)
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-800 text-white font-semibold">{{ $page }}</span>
              @else
                <a href="{{ $bookings->url($page) }}"
                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">{{ $page }}</a>
              @endif

              @php
                $historyPreviousVisiblePage = $page;
              @endphp
            @endforeach

            @if ($bookings->hasMorePages())
              <a href="{{ $bookings->nextPageUrl() }}"
                 class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">Next</a>
            @else
              <span class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed">Next</span>
            @endif
          </div>
        </div>
      </div>
    @endif
  @endif
</div>

<div id="historyBillingDetailModal"
     class="hidden fixed inset-0 z-[70]">
  <div class="absolute inset-0 bg-black/40"
       onclick="closeHistoryBookingDetailModal()"></div>
  <div class="relative z-[71] min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-xl bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-gray-900">Detail Billing Booking</h3>
          <p id="historyBillingDetailSubtitle"
             class="text-xs text-gray-500 mt-0.5">-</p>
        </div>
        <button type="button"
                onclick="closeHistoryBookingDetailModal()"
                class="text-gray-400 hover:text-gray-600 transition">✕</button>
      </div>

      <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Jumlah Orders</p>
          <p id="historyBillingDetailOrderCount"
             class="font-semibold text-gray-900 mt-0.5">0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Total Bill</p>
          <p id="historyBillingDetailTotalBill"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Mode Pembayaran</p>
          <p id="historyBillingDetailPaymentMode"
             class="font-semibold text-gray-900 mt-0.5">-</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Metode Pembayaran</p>
          <p id="historyBillingDetailPaymentMethod"
             class="font-semibold text-gray-900 mt-0.5">-</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 sm:col-span-2">
          <p class="text-xs text-gray-500">Reference Number</p>
          <p id="historyBillingDetailReferenceNumber"
             class="font-semibold text-gray-900 mt-0.5 break-words">-</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Tax</p>
          <p id="historyBillingDetailTax"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Service Charge</p>
          <p id="historyBillingDetailServiceCharge"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Sub Total (setelah tax + service)</p>
          <p id="historyBillingDetailSubTotal"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">Discount</p>
          <p id="historyBillingDetailDiscount"
             class="font-semibold text-gray-900 mt-0.5">-</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
          <p class="text-xs text-gray-500">DP</p>
          <p id="historyBillingDetailDownPayment"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 sm:col-span-2">
          <p class="text-xs text-gray-500">Sisa yang Harus Dibayar</p>
          <p id="historyBillingDetailRemainingPayment"
             class="font-semibold text-gray-900 mt-0.5">Rp 0</p>
        </div>
      </div>

      <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
        <button type="button"
                onclick="closeHistoryBookingDetailModal()"
                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div id="historyErrorModal"
     class="hidden fixed inset-0 z-[70]">
  <div class="absolute inset-0 bg-black/40"
       onclick="closeHistoryErrorModal()"></div>
  <div class="relative z-[71] min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900">Error Message</h3>
        <button type="button"
                onclick="closeHistoryErrorModal()"
                class="text-gray-400 hover:text-gray-600 transition">✕</button>
      </div>
      <div class="px-5 py-4">
        <p id="historyErrorMessageBody"
           class="text-sm text-red-600 whitespace-pre-wrap break-words">-</p>
      </div>
      <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
        <button type="button"
                onclick="closeHistoryErrorModal()"
                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Tutup</button>
      </div>
    </div>
  </div>
</div>

@php
  $historyBillingDetailMap = $bookings
      ->mapWithKeys(function ($booking) {
          $session = $booking->tableSession;
          $billing = $session?->billing;
          $paymentModeValue = strtolower((string) ($billing?->payment_mode ?? '-'));
          $totalBillAmount = (float) ($billing?->subtotal ?? 0);
          $taxAmount = (float) ($billing?->tax ?? 0);
          $serviceChargeAmount = (float) ($billing?->service_charge ?? 0);
          $subTotalAmount = $totalBillAmount + $taxAmount + $serviceChargeAmount;
          $downPaymentAmount = (float) ($booking->down_payment_amount ?? 0);

          if ($paymentModeValue === 'split') {
              $splitMethodLabels = collect();

              if ((float) ($billing?->split_cash_amount ?? 0) > 0) {
                  $splitMethodLabels->push('CASH');
              }

              if ((float) ($billing?->split_debit_amount ?? 0) > 0) {
                  $splitMethodLabels->push(strtoupper((string) ($billing?->split_non_cash_method ?? 'NON-CASH 1')));
              }

              if ((float) ($billing?->split_second_non_cash_amount ?? 0) > 0) {
                  $splitMethodLabels->push(strtoupper((string) ($billing?->split_second_non_cash_method ?? 'NON-CASH 2')));
              }

              $splitReferences = collect();

              if (filled($billing?->split_non_cash_reference_number)) {
                  $splitReferences->push('Ref 1: ' . (string) $billing->split_non_cash_reference_number);
              }

              if (filled($billing?->split_second_non_cash_reference_number)) {
                  $splitReferences->push('Ref 2: ' . (string) $billing->split_second_non_cash_reference_number);
              }

              $paymentMethodDisplay = $splitMethodLabels->isNotEmpty() ? $splitMethodLabels->implode(' + ') : 'SPLIT';

              $referenceNumberDisplay = $splitReferences->isNotEmpty() ? $splitReferences->implode(' | ') : '-';
          } else {
              $paymentMethodDisplay = strtoupper((string) ($billing?->payment_method ?? '-'));
              $referenceNumberDisplay = filled($billing?->payment_reference_number) ? (string) $billing->payment_reference_number : '-';
          }

          return [
              $booking->id => [
                  'customer' => $booking->customer->name,
                  'table' => $booking->table?->table_number ?? '-',
                  'order_count' => (int) ($session?->orders?->count() ?? 0),
                  'total_bill' => $totalBillAmount,
                  'tax_amount' => (float) ($billing?->tax ?? 0),
                  'service_charge' => (float) ($billing?->service_charge ?? 0),
                  'sub_total' => $subTotalAmount,
                  'discount_amount' => (float) ($billing?->discount_amount ?? 0),
                  'down_payment_amount' => $downPaymentAmount,
                  'remaining_payment' => (float) ($billing?->grand_total ?? 0),
                  'payment_mode' => strtoupper((string) ($billing?->payment_mode ?? '-')),
                  'payment_method' => $paymentMethodDisplay,
                  'reference_number' => $referenceNumberDisplay,
              ],
          ];
      })
      ->all();

  $historyOrdersMap = $bookings
      ->mapWithKeys(function ($booking) {
          $orders =
              $booking->tableSession?->orders
                  ?->map(function ($order) {
                      return [
                          'order_number' => $order->order_number,
                          'ordered_at' => $order->ordered_at?->setTimezone('Asia/Jakarta')?->format('d M Y H:i') ?? null,
                          'status' => $order->status,
                          'total' => (float) $order->total,
                          'items' => $order->items
                              ->map(
                                  fn($item) => [
                                      'name' => $item->item_name,
                                      'qty' => (int) $item->quantity,
                                      'price' => (float) $item->price,
                                      'subtotal' => (float) $item->subtotal,
                                  ],
                              )
                              ->values()
                              ->all(),
                      ];
                  })
                  ->values()
                  ->all() ?? [];

          return [
              $booking->id => [
                  'customer' => $booking->customer->name,
                  'table' => $booking->table?->table_number ?? '-',
                  'orders' => $orders,
              ],
          ];
      })
      ->all();
@endphp

<script>
  const historyBillingDetailData = @json($historyBillingDetailMap);
  const historyOrdersData = @json($historyOrdersMap);

  function openHistoryBookingDetailModal(bookingId) {
    const data = historyBillingDetailData[String(bookingId)] || historyBillingDetailData[bookingId];
    if (!data) {
      return;
    }

    const formatRupiah = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

    const modal = document.getElementById('historyBillingDetailModal');
    if (!modal) {
      return;
    }

    const subtitle = document.getElementById('historyBillingDetailSubtitle');
    const orderCount = document.getElementById('historyBillingDetailOrderCount');
    const totalBill = document.getElementById('historyBillingDetailTotalBill');
    const tax = document.getElementById('historyBillingDetailTax');
    const serviceCharge = document.getElementById('historyBillingDetailServiceCharge');
    const subTotal = document.getElementById('historyBillingDetailSubTotal');
    const discount = document.getElementById('historyBillingDetailDiscount');
    const downPayment = document.getElementById('historyBillingDetailDownPayment');
    const remainingPayment = document.getElementById('historyBillingDetailRemainingPayment');
    const paymentMode = document.getElementById('historyBillingDetailPaymentMode');
    const paymentMethod = document.getElementById('historyBillingDetailPaymentMethod');
    const referenceNumber = document.getElementById('historyBillingDetailReferenceNumber');

    if (subtitle) {
      subtitle.textContent = `${data.customer} — Meja ${data.table}`;
    }

    if (orderCount) {
      orderCount.textContent = Number(data.order_count || 0).toLocaleString('id-ID');
    }

    if (totalBill) {
      totalBill.textContent = formatRupiah(data.total_bill || 0);
    }

    if (tax) {
      tax.textContent = formatRupiah(data.tax_amount || 0);
    }

    if (serviceCharge) {
      serviceCharge.textContent = formatRupiah(data.service_charge || 0);
    }

    if (subTotal) {
      subTotal.textContent = formatRupiah(data.sub_total || 0);
    }

    if (discount) {
      const discountAmount = Number(data.discount_amount || 0);
      discount.textContent = discountAmount > 0 ? `- ${formatRupiah(discountAmount)}` : '-';
    }

    if (downPayment) {
      downPayment.textContent = formatRupiah(data.down_payment_amount || 0);
    }

    if (remainingPayment) {
      remainingPayment.textContent = formatRupiah(data.remaining_payment || 0);
    }

    if (paymentMode) {
      paymentMode.textContent = data.payment_mode || '-';
    }

    if (paymentMethod) {
      paymentMethod.textContent = data.payment_method || '-';
    }

    if (referenceNumber) {
      referenceNumber.textContent = data.reference_number || '-';
    }

    modal.classList.remove('hidden');
  }

  function closeHistoryBookingDetailModal() {
    const modal = document.getElementById('historyBillingDetailModal');
    if (!modal) {
      return;
    }

    modal.classList.add('hidden');
  }

  document.querySelectorAll('#bookingTableBody .booking-row').forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('button, a, form, input, select, textarea, label, details, summary')) {
        return;
      }

      const bookingId = row.dataset.bookingId;
      if (!bookingId) {
        return;
      }

      openHistoryBookingDetailModal(bookingId);
    });
  });

  function openHistoryBookingOrdersModal(bookingId) {
    const data = historyOrdersData[String(bookingId)] || historyOrdersData[bookingId];
    if (!data) {
      return;
    }

    const title = document.getElementById('orderHistoryTitle');
    const body = document.getElementById('orderHistoryBody');
    const modal = document.getElementById('orderHistoryModal');

    if (!title || !body || !modal) {
      return;
    }

    title.textContent = `${data.customer} — Meja ${data.table}`;

    if (!data.orders || data.orders.length === 0) {
      body.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">Belum ada order.</p>';
      modal.classList.remove('hidden');
      return;
    }

    body.innerHTML = data.orders.map((order) => {
      const statusClass = {
        pending: 'bg-yellow-100 text-yellow-700',
        preparing: 'bg-blue-100 text-blue-700',
        ready: 'bg-indigo-100 text-indigo-700',
        completed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
      } [order.status] || 'bg-gray-100 text-gray-600';

      const rows = order.items.map((item) => `
        <tr class="border-b border-gray-50 last:border-0">
          <td class="py-1.5 pr-3 text-sm text-gray-700">${item.name}</td>
          <td class="py-1.5 px-3 text-sm text-gray-500 text-center">${item.qty}</td>
          <td class="py-1.5 pl-3 text-sm text-gray-500 text-right">Rp ${Number(item.price).toLocaleString('id-ID')}</td>
          <td class="py-1.5 pl-3 text-sm font-medium text-gray-700 text-right">Rp ${Number(item.subtotal).toLocaleString('id-ID')}</td>
        </tr>
      `).join('');

      return `
        <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
          <div class="flex items-center justify-between bg-gray-50 px-4 py-2.5">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-semibold text-gray-600">${order.order_number}</span>
              <span class="text-xs text-gray-400">${order.ordered_at ?? ''}</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">${order.status}</span>
              <span class="text-sm font-bold text-gray-900">Rp ${Number(order.total).toLocaleString('id-ID')}</span>
            </div>
          </div>
          <table class="w-full px-4">
            <thead><tr class="bg-white">
              <th class="px-4 py-1.5 text-left text-xs text-gray-400 font-medium">Item</th>
              <th class="px-3 py-1.5 text-center text-xs text-gray-400 font-medium">Qty</th>
              <th class="px-3 py-1.5 text-right text-xs text-gray-400 font-medium">Harga</th>
              <th class="px-3 py-1.5 text-right text-xs text-gray-400 font-medium">Subtotal</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50 px-4">${rows}</tbody>
          </table>
        </div>
      `;
    }).join('');

    modal.classList.remove('hidden');
  }

  function openHistoryErrorModal(trigger) {
    const modal = document.getElementById('historyErrorModal');
    const body = document.getElementById('historyErrorMessageBody');
    if (!modal || !body) {
      return;
    }

    const message = trigger?.dataset?.errorMessage || '-';
    body.textContent = message;
    modal.classList.remove('hidden');
  }

  function closeHistoryErrorModal() {
    const modal = document.getElementById('historyErrorModal');
    const body = document.getElementById('historyErrorMessageBody');
    if (!modal || !body) {
      return;
    }

    body.textContent = '-';
    modal.classList.add('hidden');
  }
</script>
