<x-app-layout>
  <div class="p-6">

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
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Transaction Checker</h1>
          <p class="text-sm text-gray-500">Pantau dan check setiap item dalam orderan secara real-time</p>
        </div>
      </div>
      <a href="{{ request()->fullUrl() }}"
         class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-lg font-medium transition text-sm">
        <svg class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </a>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center mb-3">
          <svg class="w-5 h-5 text-slate-600"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
          </svg>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $totalOrders }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Order</p>
      </div>

      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mb-3">
          <svg class="w-5 h-5 text-red-500"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $baruOrders }}</p>
        <p class="text-sm text-gray-500 mt-1">Baru / Belum Diproses</p>
      </div>

      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mb-3">
          <svg class="w-5 h-5 text-yellow-500"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $prosesOrders }}</p>
        <p class="text-sm text-gray-500 mt-1">Sedang Diproses</p>
      </div>

      <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3">
          <svg class="w-5 h-5 text-green-500"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $selesaiOrders }}</p>
        <p class="text-sm text-gray-500 mt-1">Selesai</p>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

      <!-- Filter Tabs -->
      <div class="flex items-center gap-1 p-4 border-b border-gray-100">
        <a href="{{ route('admin.transaction-checker.index', ['tab' => 'all']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'all' ? 'bg-slate-800 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
          Semua ({{ $totalOrders }})
        </a>
        <a href="{{ route('admin.transaction-checker.index', ['tab' => 'proses']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'proses' ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
          Dalam Proses ({{ $baruOrders + $prosesOrders }})
        </a>
        <a href="{{ route('admin.transaction-checker.index', ['tab' => 'selesai']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'selesai' ? 'bg-green-500 text-white' : 'text-gray-500 hover:bg-gray-100' }}">
          Selesai ({{ $selesaiOrders }})
        </a>
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
          <p class="text-sm font-medium">Tidak ada order ditemukan</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8"></th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Order ID</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>

            @foreach ($orders as $order)
              @php
                $totalItems = $order->items->where('status', '!=', 'cancelled')->count();
                $servedItems = $order->items->where('status', 'served')->count();
                $displayId = $order->ordered_at && $order->ordered_at->isToday() ? '#TRX-TODAY-' . $order->id : '#TRX-' . $order->id;
                $activeItems = $order->items->where('status', '!=', 'cancelled');
              @endphp

              <tbody x-data="{
                  expanded: false,
                  loading: false,
                  orderStatus: '{{ $order->status }}',
                  servedCount: {{ $servedItems }},
                  totalCount: {{ $totalItems }},
                  items: @js($activeItems->map(fn($i) => ['id' => $i->id, 'item_name' => $i->item_name, 'quantity' => $i->quantity, 'price' => $i->price, 'status' => $i->status, 'preparation_location' => $i->preparation_location])->values()->toArray()),
                  get progressPct() {
                      return this.totalCount > 0 ? Math.round((this.servedCount / this.totalCount) * 100) : 0;
                  },
                  statusLabel(s) {
                      return { pending: 'Baru', preparing: 'Dalam Proses', ready: 'Siap Saji', completed: 'Selesai', cancelled: 'Dibatalkan' } [s] || s;
                  },
                  statusClass(s) {
                      return { pending: 'bg-red-100 text-red-700', preparing: 'bg-yellow-100 text-yellow-700', ready: 'bg-blue-100 text-blue-700', completed: 'bg-green-100 text-green-700', cancelled: 'bg-gray-100 text-gray-500' } [s] || 'bg-gray-100 text-gray-500';
                  },
                  async checkItem(itemId) {
                      if (this.loading) return;
                      this.loading = true;
                      try {
                          const url = '{{ route('admin.transaction-checker.check-item', ':id') }}'.replace(':id', itemId);
                          const res = await fetch(url, {
                              method: 'PATCH',
                              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                          });
                          const data = await res.json();
                          if (data.success) {
                              this.items = this.items.map(i => i.id === itemId ? { ...i, status: 'served' } : i);
                              this.servedCount = data.served_count;
                              this.totalCount = data.total_count;
                              this.orderStatus = data.order_status;
                          }
                      } finally {
                          this.loading = false;
                      }
                  },
                  async checkAll() {
                      if (this.loading || this.orderStatus === 'completed') return;
                      this.loading = true;
                      try {
                          const url = '{{ route('admin.transaction-checker.check-all', ':id') }}'.replace(':id', '{{ $order->id }}');
                          const res = await fetch(url, {
                              method: 'PATCH',
                              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                          });
                          const data = await res.json();
                          if (data.success) {
                              this.items = this.items.map(i => ({ ...i, status: 'served' }));
                              this.servedCount = this.totalCount;
                              this.orderStatus = data.order_status;
                          }
                      } finally {
                          this.loading = false;
                      }
                  }
              }">

                <!-- Main Row -->
                <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-3.5">
                    <button @click="expanded = !expanded"
                            class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                      <svg class="w-4 h-4 transition-transform duration-200"
                           :class="{ 'rotate-90': expanded }"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </td>

                  <td class="px-4 py-3.5">
                    <span class="font-mono font-semibold text-slate-800 text-xs">{{ $displayId }}</span>
                  </td>

                  <td class="px-4 py-3.5 whitespace-nowrap">
                    @if ($order->ordered_at)
                      <div class="font-medium text-gray-800">{{ $order->ordered_at->format('H:i') }}</div>
                      <div class="text-xs text-gray-400">{{ $order->ordered_at->format('d M Y') }}</div>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>

                  <td class="px-4 py-3.5">
                    @if ($order->tableSession?->customer)
                      <div class="font-medium text-gray-800">{{ $order->tableSession->customer->name }}</div>
                      @if ($order->tableSession->customer->profile?->phone)
                        <div class="text-xs text-gray-400">{{ $order->tableSession->customer->profile->phone }}</div>
                      @endif
                    @else
                      <span class="text-gray-400 text-xs">Guest</span>
                    @endif
                    @if ($order->tableSession?->table)
                      <div class="text-xs text-blue-500 font-medium mt-0.5">
                        Meja {{ $order->tableSession->table->table_number }}
                      </div>
                    @endif
                  </td>

                  <td class="px-4 py-3.5">
                    <span class="font-medium text-gray-700">{{ $totalItems }} item</span>
                  </td>

                  <td class="px-4 py-3.5 min-w-[150px]">
                    <div class="flex items-center gap-2">
                      <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                        <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-300"
                             :style="`width: ${progressPct}%`"></div>
                      </div>
                      <span class="text-xs text-gray-500 whitespace-nowrap"
                            x-text="`${servedCount}/${totalCount} ${progressPct}%`"></span>
                    </div>
                  </td>

                  <td class="px-4 py-3.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                          :class="statusClass(orderStatus)"
                          x-text="statusLabel(orderStatus)">
                    </span>
                  </td>

                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <span class="font-semibold text-gray-800">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                  </td>

                  <td class="px-4 py-3.5">
                    <button @click="checkAll()"
                            :disabled="loading || orderStatus === 'completed'"
                            :class="orderStatus === 'completed'
                                ?
                                'bg-gray-100 text-gray-400 cursor-not-allowed opacity-60' :
                                'bg-slate-800 hover:bg-slate-700 text-white'"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap">
                      <svg class="w-3.5 h-3.5"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Check All
                    </button>
                  </td>
                </tr>

                <!-- Expanded Items Row -->
                <tr x-show="expanded"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="bg-slate-50 border-t border-slate-100">
                  <td colspan="9"
                      class="px-6 py-4">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                      <template x-for="item in items"
                                :key="item.id">
                        <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
                          <div class="flex items-start justify-between gap-1 mb-1.5">
                            <span class="font-medium text-gray-800 text-xs leading-tight"
                                  x-text="item.item_name"></span>
                            <span class="shrink-0 text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono"
                                  x-text="`×${item.quantity}`"></span>
                          </div>
                          <div class="text-xs text-gray-400 mb-3"
                               x-text="`Rp ${Number(item.price).toLocaleString('id-ID')}` + (item.preparation_location ? ` · ${item.preparation_location}` : '')">
                          </div>
                          <button @click="checkItem(item.id)"
                                  :disabled="item.status === 'served' || loading"
                                  :class="item.status === 'served' ?
                                      'bg-green-500 text-white cursor-default' :
                                      'bg-slate-800 hover:bg-slate-700 text-white'"
                                  class="w-full py-1.5 rounded-lg text-xs font-medium transition flex items-center justify-center gap-1">
                            <template x-if="item.status === 'served'">
                              <span class="flex items-center gap-1">
                                <svg class="w-3 h-3"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                  <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Done
                              </span>
                            </template>
                            <template x-if="item.status !== 'served'">
                              <span>Check</span>
                            </template>
                          </button>
                        </div>
                      </template>
                    </div>
                  </td>
                </tr>

              </tbody>
            @endforeach

          </table>
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
