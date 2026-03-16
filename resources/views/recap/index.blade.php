<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      Rekapan End Day
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900">Filter Rekapan</h3>
            <p class="text-sm text-gray-500 mt-1">Pilih rentang tanggal dan jam, lalu export bila diperlukan.</p>
          </div>

          <form method="GET"
                action="{{ route('admin.recap.index') }}"
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto_auto] gap-3 w-full lg:w-auto">
            <div>
              <label for="start_datetime"
                     class="block text-sm font-medium text-gray-700 mb-1">Mulai</label>
              <input id="start_datetime"
                     type="datetime-local"
                     name="start_datetime"
                     value="{{ $selectedStartDatetime }}"
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <div>
              <label for="end_datetime"
                     class="block text-sm font-medium text-gray-700 mb-1">Sampai</label>
              <input id="end_datetime"
                     type="datetime-local"
                     name="end_datetime"
                     value="{{ $selectedEndDatetime }}"
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium min-h-[42px]">
              Filter
            </button>

            <a href="{{ route('admin.recap.export', ['start_datetime' => $selectedStartDatetime, 'end_datetime' => $selectedEndDatetime]) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-medium min-h-[42px] whitespace-nowrap">
              Export Excel (.xlsx)
            </a>
          </form>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
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

          <div class="p-4 border border-gray-200 rounded-lg bg-amber-50">
            <p class="text-sm font-medium text-amber-700">Total Pajak</p>
            <p class="text-2xl font-bold text-amber-800 mt-1">Rp {{ number_format($dashboardPreview['total_tax'] ?? 0, 0, ',', '.') }}</p>
          </div>

          <div class="p-4 border border-gray-200 rounded-lg bg-orange-50">
            <p class="text-sm font-medium text-orange-700">Total Service Charge</p>
            <p class="text-2xl font-bold text-orange-800 mt-1">Rp {{ number_format($dashboardPreview['total_service_charge'] ?? 0, 0, ',', '.') }}</p>
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
                  <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ $transaction['items_count'] }}</td>
                  <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Rp {{ number_format($transaction['total'], 0, ',', '.') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6"
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
  </div>
</x-app-layout>
