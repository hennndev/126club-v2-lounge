<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      Rekapan End Day
    </h2>
  </x-slot>

  <div class="py-6"
       x-data="{ activeTab: 'recap', showHistoryModal: false, selectedHistory: null }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
          {{ session('error') }}
        </div>
      @endif

      @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
          {{ session('success') }}
        </div>
      @endif

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900">Rekap End Day</h3>
            <p class="text-sm text-gray-500 mt-1">Pantau recap hari ini dan lihat history closing otomatis dari dashboard.</p>
          </div>

          <a href="{{ route('admin.recap.close-preview', ['start_datetime' => $selectedStartDatetime, 'end_datetime' => $selectedEndDatetime]) }}"
             target="_blank"
             rel="noopener noreferrer"
             class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-medium min-h-[42px] whitespace-nowrap">
            Preview Print Struk
          </a>
        </div>

        <div class="flex flex-col gap-4">
          <div class="flex border-b border-gray-200 gap-2">
            <button type="button"
                    @click="activeTab = 'recap'"
                    :class="activeTab === 'recap' ? '-mb-px border-b-2 border-slate-800 text-slate-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-5 py-3 text-sm transition">
              Recap
            </button>
            <button type="button"
                    @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? '-mb-px border-b-2 border-slate-800 text-slate-900 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-5 py-3 text-sm transition">
              History
            </button>
          </div>
        </div>
      </div>

      <div x-show="activeTab === 'recap'"
           class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Transaksi Kasir</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $cashierCount }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Penjualan Kasir</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">Rp {{ number_format($cashierRevenue, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Item Keluar Kitchen</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $kitchenQtyTotal }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Item Keluar Bar</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $barQtyTotal }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Penjualan Rokok (Qty)</p>
            <p class="text-2xl font-bold text-rose-700 mt-1">{{ number_format($totalPenjualanRokok ?? 0, 0, ',', '.') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">Preview Dashboard (Akumulasi)</h3>
            <span class="text-xs text-gray-500">Semua transaksi booking + walk-in</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
              <p class="text-sm font-medium text-gray-500">Total Transaksi</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($dashboardPreview['total_transactions'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-rose-50">
              <p class="text-sm font-medium text-rose-700">Total Penjualan Rokok (Qty)</p>
              <p class="text-2xl font-bold text-rose-800 mt-1">{{ number_format($dashboardPreview['total_penjualan_rokok'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-amber-50">
              <p class="text-sm font-medium text-amber-700">Total Pajak</p>
              <p class="text-2xl font-bold text-amber-800 mt-1">Rp {{ number_format($dashboardPreview['total_tax'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-orange-50">
              <p class="text-sm font-medium text-orange-700">Total Service Charge</p>
              <p class="text-2xl font-bold text-orange-800 mt-1">Rp {{ number_format($dashboardPreview['total_service_charge'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
              <p class="text-sm font-medium text-gray-500">Total Tunai</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($dashboardPreview['total_cash'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-blue-50">
              <p class="text-sm font-medium text-blue-700">Total Transfer</p>
              <p class="text-2xl font-bold text-blue-800 mt-1">Rp {{ number_format($dashboardPreview['total_transfer'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-indigo-50">
              <p class="text-sm font-medium text-indigo-700">Total Debit</p>
              <p class="text-2xl font-bold text-indigo-800 mt-1">Rp {{ number_format($dashboardPreview['total_debit'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-violet-50">
              <p class="text-sm font-medium text-violet-700">Total Kredit</p>
              <p class="text-2xl font-bold text-violet-800 mt-1">Rp {{ number_format($dashboardPreview['total_kredit'] ?? 0, 0, ',', '.') }}</p>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg bg-emerald-50">
              <p class="text-sm font-medium text-emerald-700">Total QRIS</p>
              <p class="text-2xl font-bold text-emerald-800 mt-1">Rp {{ number_format($dashboardPreview['total_qris'] ?? 0, 0, ',', '.') }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pajak</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">Rp {{ number_format($totalTax, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Service Charge</p>
            <p class="text-2xl font-bold text-orange-700 mt-1">Rp {{ number_format($totalServiceCharge, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pembayaran Tunai</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($paymentMethodTotals['cash'] ?? 0, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pembayaran Transfer</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($paymentMethodTotals['transfer'] ?? 0, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pembayaran Debit</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($paymentMethodTotals['debit'] ?? 0, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pembayaran Kredit</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($paymentMethodTotals['kredit'] ?? 0, 0, ',', '.') }}</p>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">Total Pembayaran QRIS</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($paymentMethodTotals['qris'] ?? 0, 0, ',', '.') }}</p>
          </div>

        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Kasir (Harga Ditampilkan)</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal & Jam</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. Transaksi</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Customer</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Metode Pembayaran</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. Referensi</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail Item</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty Item</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($cashierTransactions as $transaction)
                  <tr>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction['datetime'] }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $transaction['order_number'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction['customer_name'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction['payment_method'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction['payment_reference_number'] ?: '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                      <details class="group">
                        <summary class="cursor-pointer text-slate-700 hover:text-slate-900 font-medium">Lihat Item</summary>
                        <div class="mt-2 space-y-2 rounded-md border border-gray-200 bg-gray-50 p-2">
                          @forelse ($transaction['items'] as $orderItem)
                            <div class="rounded border border-gray-200 bg-white p-2">
                              <p class="text-xs text-gray-800">
                                <span class="font-semibold">{{ $orderItem['quantity'] }}x</span>
                                {{ $orderItem['name'] }}
                              </p>
                              <p class="mt-1 text-[11px] text-gray-600">Harga: Rp {{ number_format($orderItem['price'], 0, ',', '.') }}</p>
                              <p class="text-[11px] text-gray-600">Subtotal: Rp {{ number_format($orderItem['subtotal'], 0, ',', '.') }}</p>
                              @if (($orderItem['tax_amount'] ?? 0) > 0)
                                <p class="text-[11px] text-amber-700">PPN: Rp {{ number_format($orderItem['tax_amount'], 0, ',', '.') }}</p>
                              @endif
                              @if (($orderItem['service_charge_amount'] ?? 0) > 0)
                                <p class="text-[11px] text-orange-700">Service: Rp {{ number_format($orderItem['service_charge_amount'], 0, ',', '.') }}</p>
                              @endif
                            </div>
                          @empty
                            <p class="text-xs text-gray-500">Tidak ada item.</p>
                          @endforelse

                          <div class="rounded border border-gray-200 bg-white p-2 space-y-1 text-[11px]">
                            <div class="flex items-center justify-between text-gray-700">
                              <span>Total Bill</span>
                              <span>Rp {{ number_format($transaction['total_bill'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-amber-700">
                              <span>PPN</span>
                              <span>Rp {{ number_format($transaction['tax_total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-orange-700">
                              <span>Service Charge</span>
                              <span>Rp {{ number_format($transaction['service_charge_total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-700 font-medium border-t border-dashed border-gray-200 pt-1">
                              <span>Sub Total</span>
                              <span>Rp {{ number_format($transaction['sub_total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-rose-700">
                              <span>Diskon</span>
                              <span>- Rp {{ number_format($transaction['discount_amount'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-indigo-700">
                              <span>DP</span>
                              <span>Rp {{ number_format($transaction['down_payment_amount'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-900 font-semibold border-t border-dashed border-gray-200 pt-1">
                              <span>Sisa Bayar</span>
                              <span>Rp {{ number_format($transaction['total'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                          </div>
                        </div>
                      </details>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ $transaction['items_count'] }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Rp {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8"
                        class="px-4 py-6 text-sm text-center text-gray-500">Tidak ada transaksi kasir pada tanggal ini.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>


        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Item Keluar Kitchen</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal & Jam</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  @forelse ($kitchenItems as $item)
                    <tr>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ $item['datetime'] }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ $item['order_number'] }}</td>
                      <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['item_name'] }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ $item['qty'] }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4"
                          class="px-4 py-6 text-sm text-center text-gray-500">Tidak ada item kitchen pada tanggal ini.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Item Keluar Bar</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal & Jam</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                  @forelse ($barItems as $item)
                    <tr>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ $item['datetime'] }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700">{{ $item['order_number'] }}</td>
                      <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['item_name'] }}</td>
                      <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ $item['qty'] }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4"
                          class="px-4 py-6 text-sm text-center text-gray-500">Tidak ada item bar pada tanggal ini.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div x-show="activeTab === 'history'"
           class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-semibold text-gray-900">History Closing</h3>
              <p class="text-sm text-gray-500 mt-1">List snapshot dashboard yang otomatis tersimpan setiap jam 12 malam.</p>
            </div>
          </div>

          <div class="space-y-3">
            @forelse ($recapHistories as $history)
              @php
                $historyPayload = [
                    'export_url' => route('admin.recap.history.export', $history),
                    'reprint_url' => route('admin.recap.history.reprint', $history),
                    'end_day' => $history->end_day?->format('d/m/Y') ?? '-',
                    'last_synced_at' => $history->last_synced_at?->format('d/m/Y H:i') ?? '-',
                    'total_transactions' => number_format($history->total_transactions, 0, ',', '.'),
                    'total_kitchen_items' => number_format($history->total_kitchen_items, 0, ',', '.'),
                    'total_bar_items' => number_format($history->total_bar_items, 0, ',', '.'),
                    'total_amount' => 'Rp ' . number_format($history->total_amount, 0, ',', '.'),
                    'total_penjualan_rokok' => number_format($history->total_penjualan_rokok, 0, ',', '.'),
                    'total_tax' => 'Rp ' . number_format($history->total_tax, 0, ',', '.'),
                    'total_service_charge' => 'Rp ' . number_format($history->total_service_charge, 0, ',', '.'),
                    'total_cash' => 'Rp ' . number_format($history->total_cash, 0, ',', '.'),
                    'total_transfer' => 'Rp ' . number_format($history->total_transfer, 0, ',', '.'),
                    'total_debit' => 'Rp ' . number_format($history->total_debit, 0, ',', '.'),
                    'total_kredit' => 'Rp ' . number_format($history->total_kredit, 0, ',', '.'),
                    'total_qris' => 'Rp ' . number_format($history->total_qris, 0, ',', '.'),
                ];
              @endphp

              <button type="button"
                      @click="selectedHistory = {{ \Illuminate\Support\Js::from($historyPayload) }}; showHistoryModal = true"
                      class="w-full text-left rounded-xl border border-gray-200 bg-white p-4 hover:border-slate-300 hover:bg-gray-50 transition">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div>
                    <p class="text-sm text-gray-500">End Day</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $history->end_day?->format('d/m/Y') ?? '-' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Last sync: {{ $history->last_synced_at?->format('d/m/Y H:i') ?? '-' }}</p>
                  </div>
                  <div class="flex items-center justify-start lg:justify-end">
                    <span class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white">Lihat Detail</span>
                  </div>
                </div>
              </button>
            @empty
              <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">
                Belum ada history closing otomatis.
              </div>
            @endforelse
          </div>
        </div>
      </div>

      <div x-show="showHistoryModal"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           class="fixed inset-0 z-50 overflow-y-auto bg-black/60 p-4"
           @click.self="showHistoryModal = false">
        <div class="mx-auto flex min-h-full max-w-6xl items-start justify-center py-6">
          <div class="w-full rounded-2xl bg-white shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
              <div>
                <p class="text-sm text-gray-500">Detail History Closing</p>
                <h3 class="mt-1 text-2xl font-bold text-gray-900"
                    x-text="selectedHistory?.end_day ?? '-' "></h3>
                <p class="mt-1 text-sm text-gray-500">Snapshot recap tersimpan otomatis saat proses closing harian.</p>
              </div>

              <div class="flex items-center gap-2">
                <a :href="selectedHistory?.export_url ?? '#'"
                   class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                  Export History (.xlsx)
                </a>

                <form method="POST"
                      :action="selectedHistory?.reprint_url ?? '#'">
                  @csrf
                  <button type="submit"
                          class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                          :disabled="!selectedHistory">
                    Reprint
                  </button>
                </form>

                <button type="button"
                        @click="showHistoryModal = false"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                  <svg class="h-6 w-6"
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
            </div>

            <div class="space-y-6 px-6 py-6 bg-gray-50">
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Transaksi Kasir</p>
                  <p class="text-2xl font-bold text-gray-900 mt-1"
                     x-text="selectedHistory?.total_transactions ?? '0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Item Keluar Kitchen</p>
                  <p class="text-2xl font-bold text-gray-900 mt-1"
                     x-text="selectedHistory?.total_kitchen_items ?? '0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Item Keluar Bar</p>
                  <p class="text-2xl font-bold text-gray-900 mt-1"
                     x-text="selectedHistory?.total_bar_items ?? '0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Total Penjualan Kasir</p>
                  <p class="text-2xl font-bold text-emerald-700 mt-1"
                     x-text="selectedHistory?.total_amount ?? 'Rp 0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Total Penjualan Rokok (Qty)</p>
                  <p class="text-2xl font-bold text-rose-700 mt-1"
                     x-text="selectedHistory?.total_penjualan_rokok ?? '0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Total Tunai</p>
                  <p class="text-2xl font-bold text-gray-900 mt-1"
                     x-text="selectedHistory?.total_cash ?? 'Rp 0'"></p>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                  <p class="text-sm font-medium text-gray-500">Last Sync</p>
                  <p class="text-lg font-bold text-gray-900 mt-1"
                     x-text="selectedHistory?.last_synced_at ?? '-'"></p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-base font-semibold text-gray-900">Preview Dashboard (Akumulasi)</h3>
                  <span class="text-xs text-gray-500">Snapshot history closing</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                  <div class="p-4 border border-gray-200 rounded-lg bg-rose-50">
                    <p class="text-sm font-medium text-rose-700">Total Penjualan Rokok (Qty)</p>
                    <p class="text-2xl font-bold text-rose-800 mt-1"
                       x-text="selectedHistory?.total_penjualan_rokok ?? '0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-amber-50">
                    <p class="text-sm font-medium text-amber-700">Total Pajak</p>
                    <p class="text-2xl font-bold text-amber-800 mt-1"
                       x-text="selectedHistory?.total_tax ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-orange-50">
                    <p class="text-sm font-medium text-orange-700">Total Service Charge</p>
                    <p class="text-2xl font-bold text-orange-800 mt-1"
                       x-text="selectedHistory?.total_service_charge ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <p class="text-sm font-medium text-gray-500">Total Tunai</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"
                       x-text="selectedHistory?.total_cash ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-blue-50">
                    <p class="text-sm font-medium text-blue-700">Total Transfer</p>
                    <p class="text-2xl font-bold text-blue-800 mt-1"
                       x-text="selectedHistory?.total_transfer ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-indigo-50">
                    <p class="text-sm font-medium text-indigo-700">Total Debit</p>
                    <p class="text-2xl font-bold text-indigo-800 mt-1"
                       x-text="selectedHistory?.total_debit ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-violet-50">
                    <p class="text-sm font-medium text-violet-700">Total Kredit</p>
                    <p class="text-2xl font-bold text-violet-800 mt-1"
                       x-text="selectedHistory?.total_kredit ?? 'Rp 0'"></p>
                  </div>

                  <div class="p-4 border border-gray-200 rounded-lg bg-emerald-50">
                    <p class="text-sm font-medium text-emerald-700">Total QRIS</p>
                    <p class="text-2xl font-bold text-emerald-800 mt-1"
                       x-text="selectedHistory?.total_qris ?? 'Rp 0'"></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</x-app-layout>
