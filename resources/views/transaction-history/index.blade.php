<x-app-layout>
  <div class="p-6"
       x-data="transactionHistory()">

    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Riwayat Transaksi</h1>
          <p class="text-sm text-gray-500">Lihat semua transaksi yang telah dilakukan</p>
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <!-- Total Transaksi -->
      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-6 h-6 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-0.5">Total Transaksi</p>
          <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
        </div>
      </div>

      <!-- Hari Ini -->
      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center gap-4"
           style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-6 h-6 text-white"
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
          <p class="text-xs text-green-700 mb-0.5">Hari Ini</p>
          <p class="text-2xl font-bold text-gray-900">{{ $todayOrders }}</p>
        </div>
      </div>

      <!-- Pendapatan Hari Ini -->
      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-slate-700 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-6 h-6 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-0.5">Pendapatan Hari Ini</p>
          <p class="text-xl font-bold text-gray-900">
            Rp {{ number_format($todayRevenue / 1000000, 1, '.', '') }}jt
          </p>
        </div>
      </div>

      <!-- Total Pendapatan -->
      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex items-center gap-4"
           style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
        <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-6 h-6 text-white"
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
          <p class="text-xs text-amber-700 font-medium mb-0.5">Total Pendapatan</p>
          <p class="text-xl font-bold text-amber-600">
            Rp {{ number_format($totalRevenue / 1000000, 1, '.', '') }}jt
          </p>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <!-- Table Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Daftar Transaksi</h2>
        <div class="flex items-center gap-3">
          <!-- Search -->
          <form method="GET"
                action="{{ route('admin.transaction-history.index') }}">
            <div class="relative">
              <input type="text"
                     name="search"
                     value="{{ request('search') }}"
                     placeholder="Cari transaksi atau customer..."
                     class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 w-64">
              <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </form>
          <span class="text-sm text-gray-400">{{ $orders->total() }} transaksi</span>
        </div>
      </div>

      @if ($orders->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
          <svg class="w-12 h-12 mb-3 text-gray-300"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
          </svg>
          <p class="text-sm font-medium">Tidak ada transaksi ditemukan</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe / Meja</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Bayar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              @foreach ($orders as $order)
                @php
                  $isToday = $order->ordered_at && $order->ordered_at->isToday();
                  $displayId = $isToday ? 'TRX-TODAY-' . $order->id : 'TRX-' . $order->id;
                  $isBooking = $order->tableSession?->reservation !== null;
                  $tableName = $order->tableSession?->table?->table_number;
                  $customer = $order->tableSession?->customer;
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3.5 whitespace-nowrap">
                    @if ($order->ordered_at)
                      <div class="font-medium text-gray-500 text-xs">{{ $order->ordered_at->format('d M') }}</div>
                      <div class="text-xs text-gray-400">{{ $order->ordered_at->format('H:i') }}</div>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>

                  <td class="px-5 py-3.5">
                    <span class="font-mono font-semibold text-gray-800 text-sm">{{ $displayId }}</span>
                  </td>

                  <td class="px-5 py-3.5">
                    @if ($customer)
                      <span class="font-medium text-gray-800">{{ $customer->name }}</span>
                    @else
                      <span class="text-gray-400 text-xs">Guest</span>
                    @endif
                  </td>

                  <td class="px-5 py-3.5">
                    <div class="flex flex-col gap-0.5">
                      @if ($isBooking)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 w-fit">
                          Booking
                        </span>
                      @else
                        <span class="text-xs text-gray-500">Walk-in</span>
                      @endif
                      @if ($tableName)
                        <span class="text-xs text-gray-400">{{ $isBooking ? ($order->tableSession->table->area->name ?? 'VIP') . ' ' . $tableName : 'Table ' . $tableName }}</span>
                      @endif
                    </div>
                  </td>

                  <td class="px-5 py-3.5">
                    <span class="font-medium text-gray-700">{{ $order->items->count() }}</span>
                  </td>

                  <td class="px-5 py-3.5 text-right whitespace-nowrap">
                    <span class="font-semibold text-gray-800">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                  </td>

                  <td class="px-5 py-3.5 text-center">
                    <button @click="openPrintModal({
                               id: {{ $order->id }},
                               displayId: '{{ $displayId }}',
                               total: 'Rp {{ number_format($order->total, 0, ',', '.') }}',
                               customer: '{{ $customer?->name ?? 'Guest' }}',
                               time: '{{ $order->ordered_at?->format('H:i') ?? '—' }}'
                             })"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 transition text-gray-400 hover:text-gray-700">
                      <svg class="w-5 h-5"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                      </svg>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if ($orders->hasPages())
          <div class="px-5 py-4 border-t border-gray-100">
            {{ $orders->links() }}
          </div>
        @endif
      @endif
    </div>

    <!-- Print Modal -->
    <div x-show="showPrintModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="display: none;">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50"
           @click="closePrintModal()"></div>

      <!-- Modal -->
      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-700"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <h3 class="font-semibold text-gray-900">Cetak Transaksi</h3>
          </div>
          <button @click="closePrintModal()"
                  class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition">
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="px-6 py-5">
          <p class="text-sm text-gray-500 mb-4">Pilih jenis cetakan untuk transaksi ini</p>

          <!-- Transaction Info Card -->
          <div class="bg-gray-50 rounded-xl p-4 grid grid-cols-2 gap-3 mb-5 text-sm">
            <div>
              <p class="text-xs text-gray-400 mb-0.5">No. Transaksi</p>
              <p class="font-semibold text-gray-800"
                 x-text="selectedOrder?.displayId"></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Total</p>
              <p class="font-semibold text-gray-800"
                 x-text="selectedOrder?.total"></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Pelanggan</p>
              <p class="font-semibold text-gray-800"
                 x-text="selectedOrder?.customer"></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">Waktu</p>
              <p class="font-semibold text-gray-800"
                 x-text="selectedOrder?.time"></p>
            </div>
          </div>

          <!-- Print Type Label -->
          <p class="text-sm font-medium text-gray-700 mb-3">Pilih jenis cetakan:</p>

          <!-- 2x2 Print Buttons -->
          <div class="grid grid-cols-2 gap-3 mb-4">
            <!-- Struk Resmi -->
            <button @click="printReceipt('resmi')"
                    :disabled="printing || !hasPrinterFor('resmi')"
                    :title="!hasPrinterFor('resmi') ? 'Tidak ada printer Kasir' : ''"
                    :class="{ 'ring-2 ring-amber-400': hasBeenPrinted('resmi') }"
                    class="relative flex flex-col items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl py-5 px-4 font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">
              <svg class="w-7 h-7"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span x-show="!hasBeenPrinted('resmi')">Struk Resmi</span>
              <span x-show="hasBeenPrinted('resmi')" class="text-amber-300 text-xs font-bold">↺ Cetak Ulang</span>
            </button>

            <!-- Kitchen -->
            <button @click="printReceipt('kitchen')"
                    :disabled="printing || !hasPrinterFor('kitchen')"
                    :title="!hasPrinterFor('kitchen') ? 'Tidak ada printer Kitchen' : ''"
                    :class="{ 'ring-2 ring-amber-400': hasBeenPrinted('kitchen') }"
                    class="relative flex flex-col items-center justify-center gap-2 bg-orange-500 hover:bg-orange-400 text-white rounded-xl py-5 px-4 font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">
              <svg class="w-7 h-7"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span x-show="!hasBeenPrinted('kitchen')">Kitchen</span>
              <span x-show="hasBeenPrinted('kitchen')" class="text-amber-300 text-xs font-bold">↺ Cetak Ulang</span>
            </button>

            <!-- Bar -->
            <button @click="printReceipt('bar')"
                    :disabled="printing || !hasPrinterFor('bar')"
                    :title="!hasPrinterFor('bar') ? 'Tidak ada printer Bar' : ''"
                    :class="{ 'ring-2 ring-amber-400': hasBeenPrinted('bar') }"
                    class="relative flex flex-col items-center justify-center gap-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl py-5 px-4 font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">
              <svg class="w-7 h-7"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span x-show="!hasBeenPrinted('bar')">Bar</span>
              <span x-show="hasBeenPrinted('bar')" class="text-amber-300 text-xs font-bold">↺ Cetak Ulang</span>
            </button>

            <!-- Checker Meja -->
            <button @click="printReceipt('checker')"
                    :disabled="printing || !hasPrinterFor('checker')"
                    :title="!hasPrinterFor('checker') ? 'Tidak ada printer Kasir' : ''"
                    :class="{ 'ring-2 ring-amber-400': hasBeenPrinted('checker') }"
                    class="relative flex flex-col items-center justify-center gap-2 bg-green-600 hover:bg-green-500 text-white rounded-xl py-5 px-4 font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">
              <svg class="w-7 h-7"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span x-show="!hasBeenPrinted('checker')">Checker Meja</span>
              <span x-show="hasBeenPrinted('checker')" class="text-amber-300 text-xs font-bold">↺ Cetak Ulang</span>
            </button>
          </div>

          <!-- Toast message -->
          <div x-show="toastMessage"
               x-transition
               :class="toastSuccess ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
               class="rounded-lg border px-4 py-2.5 text-sm mb-3"
               style="display: none;">
            <span x-text="toastMessage"></span>
          </div>

          <!-- Close -->
          <button @click="closePrintModal()"
                  class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 text-sm font-medium transition">
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
            Tutup
          </button>
        </div>
      </div>
    </div>


  {{-- Auth Modal for Reprint --}}
  <div x-show="showAuthModal"
       x-transition.opacity
       style="display: none;"
       class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 px-4"
       @click.self="showAuthModal = false; authCode = ''; authError = '';">
    <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">
      <div class="px-6 pt-6 pb-4">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-semibold text-gray-900">Autentikasi Diperlukan</h3>
            <p class="text-xs text-gray-500">Masukkan kode harian untuk cetak ulang</p>
          </div>
        </div>

        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
          Dokumen ini sudah pernah dicetak sebelumnya. Cetak ulang memerlukan kode otorisasi harian.
        </div>

        <div class="mb-4 space-y-1.5 rounded-lg bg-gray-50 p-3 text-xs">
          <div class="flex justify-between">
            <span class="text-gray-500">No. Transaksi</span>
            <span class="font-medium text-gray-800" x-text="selectedOrder?.displayId ?? '-'"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Jenis Cetak</span>
            <span class="font-medium capitalize text-gray-800" x-text="{
              'resmi': 'Struk Resmi',
              'kitchen': 'Kitchen',
              'bar': 'Bar',
              'checker': 'Checker Meja'
            }[pendingPrintType] ?? pendingPrintType"></span>
          </div>
        </div>

        <div class="mb-1">
          <label class="mb-1.5 block text-xs font-medium text-gray-700">Kode Harian (4 digit)</label>
          <input x-model="authCode"
                 @keydown.enter="verifyAndPrint()"
                 type="password"
                 inputmode="numeric"
                 maxlength="4"
                 placeholder="••••"
                 class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-center text-2xl tracking-[0.5em] focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none" />
        </div>
        <p x-show="authError"
           x-text="authError"
           style="display: none;"
           class="mb-2 text-center text-xs font-medium text-red-600"></p>
      </div>

      <div class="flex gap-2 border-t border-gray-100 px-6 pb-6 pt-4">
        <button @click="showAuthModal = false; authCode = ''; authError = '';"
                class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
          Batal
        </button>
        <button @click="verifyAndPrint()"
                :disabled="authCode.length !== 4 || isVerifyingAuth"
                class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
          <span x-show="!isVerifyingAuth">Verifikasi & Cetak</span>
          <span x-show="isVerifyingAuth">Memverifikasi...</span>
        </button>
      </div>
    </div>
  </div>

  </div>
  <script>
    function transactionHistory() {
      return {
        showPrintModal: false,
        selectedOrder: null,
        printing: false,
        toastMessage: '',
        toastSuccess: false,
        toastTimer: null,

        printedKeys: [],
        availableLocations: @json($printerLocations),

        showAuthModal: false,
        authCode: '',
        authError: '',
        isVerifyingAuth: false,
        pendingPrintType: null,

        openPrintModal(order) {
          this.selectedOrder = order;
          this.toastMessage = '';
          this.showPrintModal = true;
        },

        closePrintModal() {
          this.showPrintModal = false;
          this.selectedOrder = null;
          this.toastMessage = '';
        },

        hasPrinterFor(type) {
          const loc = type === 'kitchen' ? 'kitchen' : type === 'bar' ? 'bar' : 'cashier';
          return this.availableLocations.includes(loc);
        },

        hasBeenPrinted(type) {
          return this.printedKeys.includes(`${this.selectedOrder?.id}-${type}`);
        },

        async printReceipt(type) {
          if (this.printing || !this.selectedOrder) return;

          if (this.hasBeenPrinted(type)) {
            this.pendingPrintType = type;
            this.authCode = '';
            this.authError = '';
            this.showAuthModal = true;
            return;
          }

          await this._doPrint(type);
        },

        async verifyAndPrint() {
          if (this.authCode.length !== 4 || this.isVerifyingAuth) return;
          this.isVerifyingAuth = true;
          this.authError = '';

          try {
            const res = await fetch('{{ route('admin.settings.daily-auth-code.verify') }}', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ code: this.authCode }),
            });
            const data = await res.json();
            if (data.valid) {
              this.showAuthModal = false;
              this.authCode = '';
              const type = this.pendingPrintType;
              this.pendingPrintType = null;
              await this._doPrint(type, true);
            } else {
              this.authError = 'Kode tidak valid. Coba lagi.';
            }
          } catch (e) {
            this.authError = 'Terjadi kesalahan. Coba lagi.';
          } finally {
            this.isVerifyingAuth = false;
          }
        },

        async _doPrint(type, isReprint = false) {
          this.printing = true;
          this.toastMessage = '';

          try {
            const url = `{{ url('admin/transaction-history') }}/${this.selectedOrder.id}/print`;
            const res = await fetch(url, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ type, is_reprint: isReprint }),
            });
            const data = await res.json();
            this.toastSuccess = data.success;
            this.toastMessage = data.message;

            if (data.success) {
              const key = `${this.selectedOrder.id}-${type}`;
              if (!this.printedKeys.includes(key)) {
                this.printedKeys.push(key);
              }
              if (this.toastTimer) clearTimeout(this.toastTimer);
              this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 3000);
            }
          } catch (e) {
            this.toastSuccess = false;
            this.toastMessage = 'Terjadi kesalahan. Coba lagi.';
          } finally {
            this.printing = false;
          }
        },
      };
    }
  </script>
</x-app-layout>
