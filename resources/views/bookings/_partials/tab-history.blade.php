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
            <th class="px-5 py-3 text-right text-sm font-semibold text-gray-600">Total Spent</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100"
               id="bookingTableBody">
          @foreach ($bookings as $booking)
            @php
              $totalSpent = $booking->tableSession?->billing?->grand_total;
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
            <tr class="hover:bg-gray-50 transition-colors booking-row"
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
              <td class="px-5 py-4 whitespace-nowrap text-right">
                @if ($totalSpent)
                  <span class="text-base font-bold text-gray-900">
                    Rp {{ number_format($totalSpent, 0, ',', '.') }}
                  </span>
                @else
                  <span class="text-gray-300 text-sm">-</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
