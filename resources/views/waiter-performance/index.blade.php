<x-app-layout>
  <div class="p-6">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6 bg-white border border-slate-200 rounded-xl p-5">
      <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-white"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-800">Waiter Performance</h1>
        <p class="text-sm text-slate-500">Monitor performa dan penjualan setiap waiter</p>
      </div>
    </div>

    <form method="GET"
          action="{{ route('admin.waiter-performance.index') }}"
          id="filterForm">

      <!-- Period & Mode -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 mb-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <!-- Date info -->
        <div class="flex items-center gap-3 flex-1">
          <svg class="w-5 h-5 text-blue-500 flex-shrink-0"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <div>
            <p class="text-xs text-slate-500">Periode</p>
            <p class="text-sm font-semibold text-slate-800">{{ now()->translatedFormat('d F Y') }}</p>
          </div>
        </div>

        <!-- Period buttons -->
        <div class="flex gap-2">
          @foreach (['today' => 'Hari Ini', 'week' => 'Minggu Ini', 'month' => 'Bulan Ini'] as $value => $label)
            <button type="submit"
                    name="period"
                    value="{{ $value }}"
                    onclick="document.getElementById('periodInput').value='{{ $value }}'"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors
                      {{ $period === $value ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
              {{ $label }}
            </button>
          @endforeach
        </div>
      </div>
      <input type="hidden"
             name="period"
             id="periodInput"
             value="{{ $period }}">

      <!-- Mode Toggle -->
      <div class="flex gap-2 mb-5">
        <button type="submit"
                name="mode"
                value="individual"
                onclick="document.getElementById('modeInput').value='individual'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                  {{ $mode === 'individual' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Individual Waiter
        </button>
        <button type="submit"
                name="mode"
                value="all"
                onclick="document.getElementById('modeInput').value='all'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                  {{ $mode === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Semua Waiter
        </button>
      </div>
      <input type="hidden"
             name="mode"
             id="modeInput"
             value="{{ $mode }}">

      @if ($mode === 'individual')
        <!-- Waiter Selector -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 mb-5">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-slate-400 flex-shrink-0"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <div class="flex-1">
              <label class="text-xs text-slate-500 block mb-1">Pilih Waiter</label>
              <select name="waiter_id"
                      onchange="this.form.submit()"
                      class="w-full border-0 bg-transparent text-sm text-slate-800 focus:outline-none cursor-pointer">
                @foreach ($waiters as $waiter)
                  <option value="{{ $waiter->id }}"
                          {{ $selectedWaiter && $selectedWaiter->id === $waiter->id ? 'selected' : '' }}>
                    {{ $waiter->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        @if ($selectedWaiter && $stats)
          <!-- Waiter Profile Card -->
          <div class="bg-gradient-to-r from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5 mb-5 flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-white text-2xl font-bold">{{ strtoupper(substr($selectedWaiter->name, 0, 1)) }}</span>
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-800">{{ $selectedWaiter->name }}</h2>
              <p class="text-sm text-slate-500">
                ID: {{ $selectedWaiter->internalUser?->accurate_id ? 'W' . str_pad($selectedWaiter->internalUser->accurate_id, 3, '0', STR_PAD_LEFT) : 'W' . str_pad($selectedWaiter->id, 3, '0', STR_PAD_LEFT) }}
                @if ($selectedWaiter->internalUser?->area)
                  · {{ $selectedWaiter->internalUser->area->name }}
                @endif
              </p>
            </div>
          </div>

          <!-- Stat Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-600 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="text-sm text-blue-200 mb-1">Total Revenue Sesi</p>
              <p class="text-2xl font-bold">Rp {{ number_format($stats['sessionRevenue'], 0, ',', '.') }}</p>
              <p class="text-xs text-blue-300 mt-1">Termasuk min. charge</p>
            </div>

            <div class="bg-teal-600 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <p class="text-sm text-teal-200 mb-1">Customer Ditangani</p>
              <p class="text-2xl font-bold">{{ $stats['customersHandled'] }}</p>
              <p class="text-xs text-teal-300 mt-1">{{ $stats['completedSessions'] }} selesai</p>
            </div>

            <div class="bg-purple-600 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <p class="text-sm text-purple-200 mb-1">Total Order</p>
              <p class="text-2xl font-bold">{{ number_format($stats['totalTransactions']) }}</p>
              <p class="text-xs text-purple-300 mt-1">Rp {{ number_format($stats['totalOrderRevenue'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-amber-500 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
              <p class="text-sm text-amber-100 mb-1">Rata-rata / Customer</p>
              @php
                $avgPerCustomer = $stats['customersHandled'] > 0 ? $stats['sessionRevenue'] / $stats['customersHandled'] : 0;
              @endphp
              <p class="text-2xl font-bold">Rp {{ number_format($avgPerCustomer, 0, ',', '.') }}</p>
            </div>

            <div class="bg-indigo-600 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="text-sm text-indigo-200 mb-1">Avg Durasi Sesi</p>
              @php
                $avgH = (int) floor($stats['avgDurationMinutes'] / 60);
                $avgM = $stats['avgDurationMinutes'] % 60;
              @endphp
              <p class="text-2xl font-bold">
                @if ($stats['avgDurationMinutes'] === 0)
                  —
                @elseif ($avgH > 0)
                  {{ $avgH }}j {{ $avgM }}m
                @else
                  {{ $avgM }}m
                @endif
              </p>
            </div>

            <div class="bg-green-600 rounded-xl p-5 text-white">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 opacity-80"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
              </div>
              <p class="text-sm text-green-200 mb-1">Peringkat</p>
              <p class="text-2xl font-bold">#{{ $rank }}</p>
            </div>
          </div>

          <!-- Bottom: Top 5 & Recent Transactions -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Top 5 Produk -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-green-500"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="font-bold text-slate-800">Top 5 Produk</h3>
              </div>
              <p class="text-xs text-slate-500 mb-4">Produk terlaris periode ini</p>

              @if ($topProducts->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                  <svg class="w-12 h-12 mb-3 opacity-30"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                  </svg>
                  <p class="text-sm">Belum ada data produk</p>
                </div>
              @else
                <div class="space-y-3">
                  @foreach ($topProducts as $i => $product)
                    <div class="flex items-center gap-3">
                      <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $product->item_name }}</p>
                        <p class="text-xs text-slate-500">{{ number_format($product->total_qty) }}x terjual</p>
                      </div>
                      <span class="text-sm font-semibold text-slate-700 flex-shrink-0">
                        Rp {{ number_format($product->total_revenue, 0, ',', '.') }}
                      </span>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>

            <!-- Sesi Terakhir Ditangani -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-4">
                <svg class="w-4 h-4 text-blue-500"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="font-bold text-slate-800">Sesi Terakhir Ditangani</h3>
              </div>
              <p class="text-xs text-slate-500 mb-4">10 sesi terbaru</p>

              @if ($recentSessions->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                  <svg class="w-12 h-12 mb-3 opacity-30"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  <p class="text-sm">Belum ada sesi</p>
                </div>
              @else
                <div class="space-y-2">
                  @foreach ($recentSessions as $sess)
                    @php
                      $sessDuration = $sess->checked_in_at && $sess->checked_out_at ? abs(\Carbon\Carbon::parse($sess->checked_out_at)->diffInMinutes(\Carbon\Carbon::parse($sess->checked_in_at))) : null;
                      $sessDurationStr = $sessDuration !== null ? ($sessDuration >= 60 ? floor($sessDuration / 60) . 'j ' . $sessDuration % 60 . 'm' : $sessDuration . 'm') : null;

                      // Use finalized grand_total for paid, else compute live from orders_total
                      if ($sess->billing_status === 'paid' && $sess->grand_total) {
                          $displayTotal = (float) $sess->grand_total;
                      } elseif ($sess->orders_total !== null) {
                          $ot = (float) $sess->orders_total;
                          $displayTotal = $ot - (float) ($sess->discount_amount ?? 0);
                      } else {
                          $displayTotal = null;
                      }
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                      <div>
                        <p class="text-sm font-medium text-slate-800">{{ $sess->customer_name ?? 'Tamu' }}</p>
                        <p class="text-xs text-slate-500">
                          Meja {{ $sess->table_number }}
                          · {{ \Carbon\Carbon::parse($sess->checked_in_at)->setTimezone('Asia/Jakarta')->format('d M, H:i') }}
                          @if ($sessDurationStr)
                            · {{ $sessDurationStr }}
                          @endif
                        </p>
                      </div>
                      <div class="text-right">
                        <p class="text-sm font-semibold text-slate-800">
                          {{ $displayTotal !== null ? 'Rp ' . number_format($displayTotal, 0, ',', '.') : '—' }}
                        </p>
                        @php
                          $statusMap = [
                              'active' => ['bg-blue-100 text-blue-700', 'Aktif'],
                              'completed' => ['bg-green-100 text-green-700', 'Selesai'],
                              'pending' => ['bg-yellow-100 text-yellow-700', 'Pending'],
                          ];
                          [$badgeCls, $badgeLabel] = $statusMap[$sess->status] ?? ['bg-gray-100 text-gray-600', ucfirst($sess->status)];
                        @endphp
                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $badgeCls }}">{{ $badgeLabel }}</span>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        @elseif ($waiters->isEmpty())
          <div class="bg-white border border-slate-200 rounded-xl p-12 text-center text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <p class="text-sm">Tidak ada waiter aktif ditemukan.</p>
          </div>
        @endif
      @else
        <!-- All Waiters Table -->
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
          <div class="p-5 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Performa Semua Waiter</h3>
            <p class="text-sm text-slate-500 mt-0.5">
              {{ $period === 'today' ? 'Hari Ini' : ($period === 'week' ? 'Minggu Ini' : 'Bulan Ini') }}
            </p>
          </div>

          @if ($allWaitersStats->isEmpty())
            <div class="p-12 text-center text-slate-400">
              <p class="text-sm">Tidak ada data.</p>
            </div>
          @else
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-8">#</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Waiter</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Order</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg / Customer</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Revenue Sesi</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Detail</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @foreach ($allWaitersStats as $i => $ws)
                    <tr class="hover:bg-slate-50 transition-colors">
                      <td class="px-5 py-4">
                        @if ($i === 0)
                          <span class="text-base">🥇</span>
                        @elseif ($i === 1)
                          <span class="text-base">🥈</span>
                        @elseif ($i === 2)
                          <span class="text-base">🥉</span>
                        @else
                          <span class="text-slate-500 font-medium">{{ $i + 1 }}</span>
                        @endif
                      </td>
                      <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                          <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white text-xs font-bold">{{ strtoupper(substr($ws->user->name, 0, 1)) }}</span>
                          </div>
                          <div>
                            <p class="font-medium text-slate-800">{{ $ws->user->name }}</p>
                          </div>
                        </div>
                      </td>
                      <td class="px-5 py-4 text-right text-slate-700 font-medium">{{ number_format($ws->customersHandled) }}</td>
                      <td class="px-5 py-4 text-right text-slate-700">{{ number_format($ws->totalTransactions) }}</td>
                      <td class="px-5 py-4 text-right text-slate-700">Rp {{ number_format($ws->avgPerCustomer, 0, ',', '.') }}</td>
                      <td class="px-5 py-4 text-right font-semibold text-slate-800">Rp {{ number_format($ws->sessionRevenue, 0, ',', '.') }}</td>
                      <td class="px-5 py-4 text-center">
                        <a href="{{ route('admin.waiter-performance.index', ['mode' => 'individual', 'period' => $period, 'waiter_id' => $ws->user->id]) }}"
                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                          Lihat →
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      @endif

    </form>
  </div>
</x-app-layout>
