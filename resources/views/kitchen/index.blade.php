<x-app-layout>
  <div class="p-6"
       x-data="kitchenOrdersApp()"
       x-init="init()">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <div class="bg-orange-500 rounded-lg p-2">
            <svg class="w-6 h-6 text-white"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
          </div>
          Kitchen Orders
        </h1>
        <div class="flex items-center gap-3">
          <span x-show="isLoading"
                class="text-sm text-gray-500 flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin"
                 fill="none"
                 viewBox="0 0 24 24">
              <circle class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"></circle>
              <path class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating...
          </span>
          <button @click="fetchOrders()"
                  :disabled="isLoading"
                  class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 flex items-center gap-2">
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
          </button>
        </div>
      </div>
      <p class="text-sm text-gray-500 mt-1">Monitor dan kelola order makanan (Food items)</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total</p>
            <p class="text-3xl font-bold text-gray-900"
               x-text="stats.total"></p>
          </div>
          <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-gray-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-red-600 font-medium">Baru</p>
            <p class="text-3xl font-bold text-red-700"
               x-text="stats.baru"></p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-yellow-700 font-medium">Proses</p>
            <p class="text-3xl font-bold text-yellow-800"
               x-text="stats.proses"></p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-yellow-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-green-50 border border-green-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-green-600 font-medium">Selesai</p>
            <p class="text-3xl font-bold text-green-700"
               x-text="stats.selesai"></p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-slate-800 text-white rounded-xl p-4 mb-6">
      <div class="flex items-center gap-4">
        <button @click="filterByStatus(null)"
                :class="currentStatus === null ? 'bg-white bg-opacity-20' : ''"
                class="px-4 py-2 rounded-lg font-medium transition hover:bg-white hover:bg-opacity-20">
          Semua (<span x-text="stats.total"></span>)
        </button>
        <button @click="filterByStatus('proses')"
                :class="currentStatus === 'proses' ? 'bg-white bg-opacity-20' : ''"
                class="px-4 py-2 rounded-lg font-medium transition hover:bg-white hover:bg-opacity-20">
          ⏳ Dalam Proses (<span x-text="stats.proses"></span>)
        </button>
        <button @click="filterByStatus('selesai')"
                :class="currentStatus === 'selesai' ? 'bg-white bg-opacity-20' : ''"
                class="px-4 py-2 rounded-lg font-medium transition hover:bg-white hover:bg-opacity-20">
          ✅ Selesai (<span x-text="stats.selesai"></span>)
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div x-show="orders.length === 0 && !isLoading"
         class="bg-gradient-to-br from-orange-50 to-white border-2 border-dashed border-orange-300 rounded-xl p-12 text-center">
      <div class="flex items-center justify-center w-20 h-20 mx-auto bg-orange-100 rounded-full mb-4">
        <svg class="w-10 h-10 text-orange-500"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">Tidak Ada Order</h3>
      <p class="text-gray-500">Belum ada order makanan yang masuk ke kitchen</p>
    </div>

    <!-- Orders Table -->
    <div x-show="orders.length > 0"
         class="bg-white rounded-xl shadow-sm border border-gray-200">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12"></th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Meja</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Food Items</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <template x-for="order in orders"
                    :key="order.id">
            <tbody class="bg-white divide-y divide-gray-200"
                   x-data="{ expanded: true }">
              <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-4">
                  <button @click="expanded = !expanded"
                          class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5 transform transition-transform"
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
                <td class="px-4 py-4">
                  <div class="font-bold text-gray-900"
                       x-text="order.order_number"></div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-900"
                       x-text="order.created_at"></div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-blue-600"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900"
                           x-text="order.customer?.name ?? 'N/A'"></div>
                      <div class="text-xs text-gray-500"
                           x-text="order.customer?.phone ?? 'N/A'"></div>
                      <div class="text-xs text-orange-600 font-medium"
                           x-text="'🪑 ' + (order.table?.area?.name ?? 'N/A') + ' ' + (order.table?.table_number ?? 'N/A')"></div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="px-3 py-1 text-sm font-medium rounded bg-orange-100 text-orange-700"
                        x-text="order.items.length + ' items'"></span>
                </td>
                <td class="px-4 py-4">
                  <div class="w-full">
                    <div class="flex items-center justify-between text-xs mb-1">
                      <span class="font-medium text-gray-700"
                            x-text="getCompletedCount(order) + '/' + order.items.length"></span>
                      <span class="font-bold text-gray-900"
                            x-text="order.progress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div class="bg-gray-900 h-2 rounded-full transition-all"
                           :style="'width: ' + order.progress + '%'"></div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span x-show="order.status === 'selesai'"
                        class="px-3 py-1 text-xs font-medium rounded bg-green-100 text-green-700 inline-flex items-center gap-1">
                    ✓ SELESAI
                  </span>
                  <span x-show="order.status === 'proses'"
                        class="px-3 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-700 inline-flex items-center gap-1">
                    ⏳ PROSES
                  </span>
                  <span x-show="order.status === 'baru'"
                        class="px-3 py-1 text-xs font-medium rounded bg-red-100 text-red-700 inline-flex items-center gap-1">
                    ⚠ BARU
                  </span>
                </td>
                <td class="px-4 py-4">
                  <button x-show="order.status !== 'selesai'"
                          @click="completeAll(order.id)"
                          :disabled="processingOrderId === order.id"
                          class="px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="processingOrderId === order.id"
                         class="w-4 h-4 animate-spin"
                         fill="none"
                         viewBox="0 0 24 24">
                      <circle class="opacity-25"
                              cx="12"
                              cy="12"
                              r="10"
                              stroke="currentColor"
                              stroke-width="4"></circle>
                      <path class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="processingOrderId === order.id ? 'Processing...' : '✓ Selesai Semua'"></span>
                  </button>
                </td>
              </tr>
              <!-- Expandable Row -->
              <tr x-show="expanded"
                  x-transition:enter="transition ease-out duration-200"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition ease-in duration-150"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  class="bg-gray-50">
                <td colspan="8"
                    class="px-4 py-4">
                  <div class="pl-12">
                    <div class="flex items-center gap-2 mb-3">
                      <svg class="w-5 h-5 text-orange-600"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <h4 class="font-bold text-gray-900">Food Items (<span x-text="order.items.length"></span>)</h4>
                    </div>
                    <div class="space-y-2">
                      <template x-for="item in order.items"
                                :key="item.id">
                        <div class="flex items-center justify-between bg-white border rounded-lg p-3 transition"
                             :class="item.is_completed ? 'border-green-200' : 'border-gray-200'">
                          <div class="flex items-center gap-3 flex-1">
                            <button @click="toggleItem(item.id, order.id)"
                                    :disabled="processingItemId === item.id"
                                    class="w-6 h-6 rounded flex items-center justify-center transition hover:scale-110 disabled:opacity-50"
                                    :class="item.is_completed ? 'bg-green-500' : 'bg-gray-300'">
                              <svg x-show="item.is_completed"
                                   class="w-4 h-4 text-white"
                                   fill="none"
                                   stroke="currentColor"
                                   viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="3"
                                      d="M5 13l4 4L19 7" />
                              </svg>
                              <svg x-show="processingItemId === item.id"
                                   class="w-4 h-4 text-gray-600 animate-spin"
                                   fill="none"
                                   viewBox="0 0 24 24">
                                <circle class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75"
                                      fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                              </svg>
                            </button>
                            <div class="flex-1">
                              <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900"
                                      :class="{ 'line-through text-gray-500': item.is_completed }"
                                      x-text="item.recipe_name"></span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded bg-orange-100 text-orange-700">Food</span>
                              </div>
                              <div class="text-sm text-gray-500">
                                Qty: <span x-text="item.quantity"></span>
                              </div>
                            </div>
                          </div>
                          <button x-show="item.is_completed"
                                  @click="toggleItem(item.id, order.id)"
                                  :disabled="processingItemId === item.id"
                                  class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 transition disabled:opacity-50">
                            Undo
                          </button>
                        </div>
                      </template>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </template>
        </table>
      </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         @click="showToast = false"
         class="fixed bottom-4 right-4 z-[70] cursor-pointer">
      <div :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
           class="px-6 py-3 rounded-lg shadow-lg text-white font-medium flex items-center gap-2">
        <svg x-show="toastType === 'success'"
             class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7" />
        </svg>
        <svg x-show="toastType === 'error'"
             class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span x-text="toastMessage"></span>
      </div>
    </div>
  </div>

  <script>
    function kitchenOrdersApp() {
      console.log({!! json_encode($orders) !!})
      return {
        orders: {!! json_encode(
            $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'progress' => $order->progress,
                        'created_at' => $order->created_at->format('d M Y H:i'),
                        'customer' => $order->customer
                            ? [
                                'id' => $order->customer->id,
                                'name' => $order->customer->user->name ?? $order->customer->name,
                                'phone' => $order->customer->profile->phone ?? null,
                            ]
                            : null,
                        'table' => $order->table
                            ? [
                                'id' => $order->table->id,
                                'table_number' => $order->table->number ?? $order->table->table_number,
                                'area' => $order->table->area
                                    ? [
                                        'id' => $order->table->area->id,
                                        'name' => $order->table->area->name,
                                    ]
                                    : null,
                            ]
                            : null,
                        'items' => $order->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'recipe_id' => $item->bom_recipe_id,
                                    'recipe_name' => $item->recipe?->inventoryItem?->name ?? 'Unknown',
                                    'quantity' => $item->quantity,
                                    'is_completed' => $item->is_completed,
                                ];
                            })->values(),
                    ];
                })->values(),
        ) !!},
        stats: {!! json_encode(['total' => $totalOrders, 'baru' => $baruOrders, 'proses' => $prosesOrders, 'selesai' => $selesaiOrders]) !!},
        currentStatus: null,
        isLoading: false,
        processingItemId: null,
        processingOrderId: null,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        pollInterval: null,

        init() {
          // Start polling for updates every 30 seconds
          this.pollInterval = setInterval(() => {
            this.fetchOrders(true);
          }, 30000);
        },

        getCompletedCount(order) {
          return order.items.filter(item => item.is_completed).length;
        },

        async fetchOrders(silent = false) {
          if (this.isLoading && !silent) return;

          if (!silent) {
            this.isLoading = true;
          }

          try {
            const params = new URLSearchParams();
            if (this.currentStatus) {
              params.append('status', this.currentStatus);
            }

            const response = await fetch(`{{ route('admin.kitchen.fetch') }}?${params.toString()}`, {
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
              }
            });

            const data = await response.json();

            if (data.success) {
              this.orders = data.orders;
              this.stats = data.stats;
            }
          } catch (error) {
            console.error('Error fetching orders:', error);
          } finally {
            this.isLoading = false;
          }
        },

        filterByStatus(status) {
          this.currentStatus = status;
          this.fetchOrders();
        },

        async toggleItem(itemId, orderId) {
          if (this.processingItemId) return;
          this.processingItemId = itemId;

          try {
            const response = await fetch(`{{ route('admin.kitchen.toggle-item', '__ITEM_ID__') }}`.replace('__ITEM_ID__', itemId), {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
              }
            });

            const data = await response.json();

            if (data.success) {
              // Update the specific order in the list
              const orderIndex = this.orders.findIndex(o => o.id === orderId);
              if (orderIndex !== -1) {
                this.orders[orderIndex] = data.order;
              }
              this.showToastMessage(data.message, 'success');
            } else {
              this.showToastMessage(data.message || 'Failed to update item', 'error');
            }
          } catch (error) {
            console.error('Error toggling item:', error);
            this.showToastMessage('Failed to update item status', 'error');
          } finally {
            this.processingItemId = null;
          }
        },

        async completeAll(orderId) {
          if (this.processingOrderId) return;
          this.processingOrderId = orderId;

          try {
            const response = await fetch(`{{ route('admin.kitchen.complete-all', '__ORDER_ID__') }}`.replace('__ORDER_ID__', orderId), {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
              }
            });

            const data = await response.json();

            if (data.success) {
              // Remove order from list (default view excludes selesai)
              this.orders = this.orders.filter(o => o.id !== orderId);
              // Update stats
              if (this.stats) {
                this.stats.proses = Math.max(0, (this.stats.proses || 0) - 1);
                this.stats.selesai = (this.stats.selesai || 0) + 1;
              }
              this.showToastMessage(data.message, 'success');
            } else {
              this.showToastMessage(data.message || 'Failed to complete order', 'error');
            }
          } catch (error) {
            console.error('Error completing order:', error);
            this.showToastMessage('Failed to complete order', 'error');
          } finally {
            this.processingOrderId = null;
          }
        },

        showToastMessage(message, type = 'success') {
          this.toastMessage = message;
          this.toastType = type;
          this.showToast = true;
          setTimeout(() => {
            this.showToast = false;
          }, 3000);
        },

        destroy() {
          if (this.pollInterval) {
            clearInterval(this.pollInterval);
          }
        }
      };
    }
  </script>
</x-app-layout>
