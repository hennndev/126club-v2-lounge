<x-app-layout>
  <div class="py-12"
       x-data="barOrdersApp()"
       x-init="init()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Title Section -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center">
            <div class="bg-purple-600 rounded-lg p-2 mr-3">
              <svg class="w-6 h-6 text-white"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Bar Orders</h2>
              <p class="text-sm text-gray-600">Monitor dan kelola order minuman (Beverage items)</p>
            </div>
          </div>
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
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition disabled:opacity-50 flex items-center gap-2">
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

      <!-- Orders Table -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div x-show="orders.length === 0 && !isLoading"
               class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada order</h3>
            <p class="mt-1 text-sm text-gray-500">Belum ada order bar yang tersedia.</p>
          </div>

          <div x-show="orders.length > 0"
               class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Meja</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beverage Items</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <template x-for="order in orders"
                        :key="order.id">
                <tbody class="bg-white divide-y divide-gray-200"
                       x-data="{ expanded: true }">
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <button @click="expanded = !expanded"
                                type="button"
                                class="mr-2 text-gray-400 hover:text-gray-600 focus:outline-none">
                          <svg class="h-5 w-5 transform transition-transform duration-200"
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
                        <span class="text-sm font-medium text-gray-900"
                              x-text="order.order_number"></span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900"
                           x-text="order.created_at"></div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                          <svg class="h-5 w-5 text-blue-600"
                               fill="none"
                               stroke="currentColor"
                               viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                        </div>
                        <div class="ml-3">
                          <div class="text-sm font-medium text-gray-900"
                               x-text="order.customer?.name ?? 'N/A'"></div>
                          <div class="text-xs text-gray-500"
                               x-text="order.customer?.phone ?? 'N/A'"></div>
                        </div>
                      </div>
                      <div class="text-xs text-purple-600 mt-1"
                           x-text="(order.table?.area?.name ?? 'N/A') + ' - Meja ' + (order.table?.table_number ?? 'N/A')"></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm text-gray-900 font-medium"
                            x-text="order.items.length + ' items'"></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm font-medium text-gray-900"
                            x-text="getCompletedCount(order) + '/' + order.items.length"></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span x-show="order.status === 'baru'"
                            class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-md bg-pink-100 text-pink-700 uppercase">
                        Baru
                      </span>
                      <span x-show="order.status === 'proses'"
                            class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-md bg-yellow-100 text-yellow-700 uppercase">
                        Proses
                      </span>
                      <span x-show="order.status === 'selesai'"
                            class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-md bg-green-100 text-green-700 uppercase">
                        Selesai
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button x-show="order.status !== 'selesai'"
                              @click="completeAll(order.id)"
                              :disabled="processingOrderId === order.id"
                              class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition disabled:opacity-50 flex items-center gap-2">
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
                        <span x-text="processingOrderId === order.id ? 'Processing...' : 'Selesai Semua'"></span>
                      </button>
                    </td>
                  </tr>
                  <tr x-show="expanded"
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0 transform scale-95"
                      x-transition:enter-end="opacity-100 transform scale-100"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100 transform scale-100"
                      x-transition:leave-end="opacity-0 transform scale-95"
                      class="bg-gray-50">
                    <td colspan="7"
                        class="px-6 py-4">
                      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center mb-4">
                          <svg class="h-5 w-5 text-purple-600 mr-2"
                               fill="none"
                               stroke="currentColor"
                               viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                          </svg>
                          <h4 class="font-bold text-gray-900">Beverage Items (<span x-text="order.items.length"></span>)</h4>
                        </div>
                        <div class="space-y-2">
                          <template x-for="item in order.items"
                                    :key="item.id">
                            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100 hover:border-purple-200 transition">
                              <div class="flex items-center flex-1">
                                <input type="checkbox"
                                       :checked="item.is_completed"
                                       @change="toggleItem(item.id, order.id)"
                                       :disabled="processingItemId === item.id"
                                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer disabled:opacity-50">
                                <div class="ml-3 flex-1">
                                  <div class="flex items-center">
                                    <span class="text-sm font-semibold text-gray-900"
                                          :class="{ 'line-through text-gray-400': item.is_completed }"
                                          x-text="item.recipe_name"></span>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded bg-purple-100 text-purple-700">
                                      Beverage
                                    </span>
                                  </div>
                                  <div class="text-xs text-gray-500 mt-1">
                                    Qty: <span x-text="item.quantity"></span>
                                  </div>
                                </div>
                              </div>
                              <button x-show="!item.is_completed"
                                      @click="toggleItem(item.id, order.id)"
                                      :disabled="processingItemId === item.id"
                                      class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-medium transition disabled:opacity-50 flex items-center gap-1">
                                <svg x-show="processingItemId === item.id"
                                     class="w-3 h-3 animate-spin"
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
                                <span x-text="processingItemId === item.id ? '...' : 'Selesai'"></span>
                              </button>
                              <span x-show="item.is_completed"
                                    class="ml-3 text-green-600 text-xs font-medium flex items-center">
                                <svg class="h-4 w-4 mr-1"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                  <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Selesai
                              </span>
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
    function barOrdersApp() {
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
                                    'recipe_name' => $item->recipe?->inventoryItem?->name ?? $item->inventoryItem?->name ?? 'Unknown',
                                    'quantity' => $item->quantity,
                                    'is_completed' => $item->is_completed,
                                ];
                            })->values(),
                    ];
                })->values(),
        ) !!},
        stats: {!! json_encode($stats) !!},
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

            const response = await fetch(`{{ route('admin.bar.fetch') }}?${params.toString()}`, {
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
            const response = await fetch(`{{ route('admin.bar.toggle-item', '__ITEM_ID__') }}`.replace('__ITEM_ID__', itemId), {
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
            const response = await fetch(`{{ route('admin.bar.complete-all', '__ORDER_ID__') }}`.replace('__ORDER_ID__', orderId), {
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
