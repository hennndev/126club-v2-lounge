<x-app-layout>
  <div class="flex w-full h-[calc(100vh-6rem)]"
       x-data="posApp">

    <!-- Products Section -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Search & Filter Bar -->
      <div class="flex items-center gap-3 px-6 pt-6 pb-4 flex-shrink-0">
        <div class="flex-1 relative">
          <form method="GET"
                action="{{ route('admin.pos.index') }}">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari produk..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
          </form>
        </div>
        <select class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 min-w-[130px]">
          <option value="all">All</option>
          <option value="drink">Drink</option>
          <option value="food">Food</option>
          <option value="bar">Bar</option>
        </select>
        <select id="counterLocationSelect"
                class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 min-w-[160px]"
                x-model="counterLocation"
                @change="selectCounter($event)">
          <option value="">— Pilih Counter —</option>
          @foreach ($printerLocations as $group => $locations)
            <optgroup label="{{ $group }}">
              @foreach ($locations as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
        <span x-show="counterLocation"
              x-text="getCounterLabel()"
              class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full whitespace-nowrap"></span>
      </div>

      <!-- Products Grid -->
      <div class="overflow-y-auto flex-1 px-6 pb-6">
        <div class="grid grid-cols-4 gap-4">
          @forelse($products as $product)
            @php
              $category = strtolower($product['category'] ?? 'drink');
              $isFood = $category === 'food';
              $gradientClass = $isFood ? 'from-orange-500 to-red-600' : 'from-blue-400 to-cyan-500';
              $dotColor = $isFood ? 'bg-orange-400' : 'bg-blue-300';
              $outOfStock = isset($product['type']) && $product['type'] === 'item' && ($product['stock'] ?? 0) <= 0;
              $unavailable = isset($product['type']) && $product['type'] === 'bom' && ! ($product['is_available'] ?? true);
              $disabled = $outOfStock || $unavailable;
            @endphp
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col {{ $disabled ? 'opacity-60' : '' }}">
              <div class="px-3 pt-3 pb-1">
                <span class="font-bold text-gray-900 text-sm">Rp {{ number_format($product['price'], 0, ',', '.') }}</span>
              </div>
              <div class="relative bg-gradient-to-br {{ $gradientClass }} mx-3 rounded-xl overflow-hidden">
                <div class="absolute top-2 right-2 z-10 w-2.5 h-2.5 rounded-full {{ $dotColor }} opacity-80"></div>
                @if ($unavailable)
                  <div class="absolute inset-0 z-20 flex items-center justify-center bg-black/40 rounded-xl">
                    <span class="px-2 py-0.5 bg-red-600 text-white text-xs font-bold rounded">Habis</span>
                  </div>
                @endif
                @if (!$isFood && isset($product['stock']))
                  <div class="absolute bottom-2 left-2 z-10">
                    @if (($product['stock'] ?? 0) > 10)
                      <span class="px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded">Stock: {{ $product['stock'] }}</span>
                    @elseif(($product['stock'] ?? 0) > 0)
                      <span class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded">Stock: {{ $product['stock'] }}</span>
                    @else
                      <span class="px-2 py-0.5 bg-gray-500 text-white text-xs font-bold rounded">Stock: 0</span>
                    @endif
                  </div>
                @endif
                <div class="h-28 flex items-center justify-center">
                  @if ($isFood)
                    {{-- Food: plate with fork & knife --}}
                    <svg class="w-16 h-16 text-white/80"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <circle cx="12"
                              cy="12"
                              r="9"
                              stroke-width="1.5" />
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 7v4a2 2 0 004 0V7M11 11v6M15 7v10" />
                    </svg>
                  @else
                    {{-- Beverage: cocktail glass --}}
                    <svg class="w-16 h-16 text-white/80"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M7 3l2 6h6l2-6H7z" />
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M5 9h14" />
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 21h6M12 9v12" />
                    </svg>
                  @endif
                </div>
              </div>
              <div class="px-3 pt-2 pb-3 flex-1 flex flex-col justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 text-sm truncate">{{ $product['name'] }}</h3>
                  <p class="text-xs text-gray-400 mt-0.5 capitalize">{{ ucfirst($product['category']) }}</p>
                </div>
                <div class="flex items-center justify-between mt-2">
                  <span class="text-sm font-bold text-gray-900">Rp {{ number_format($product['price'] / 1000, 0, ',', '.') }}K</span>
                  <button type="button"
                          @click="addToCart('{{ $product['id'] }}')"
                          :disabled="isProcessing || {{ $disabled ? 'true' : 'false' }}"
                          class="w-8 h-8 bg-gray-900 hover:bg-gray-700 text-white rounded-lg flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed flex-shrink-0">
                    <svg x-show="!isProcessing"
                         class="w-4 h-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M12 4v16m8-8H4" />
                    </svg>
                    <svg x-show="isProcessing"
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
                  </button>
                </div>
              </div>
            </div>
          @empty
            <div class="col-span-4 text-center py-16">
              <svg class="mx-auto h-12 w-12 text-gray-300"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 class="mt-3 text-sm font-semibold text-gray-900">Tidak ada produk</h3>
              <p class="mt-1 text-sm text-gray-400">Produk belum tersedia.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <!-- Cart Panel -->
    <div class="w-96 bg-white border-l border-gray-100 flex flex-col h-full">
      <!-- Cart Header -->
      <div class="px-5 pt-5 pb-3 border-b border-gray-100 flex-shrink-0">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white"
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
              <h3 class="font-bold text-gray-900 text-base leading-tight">Keranjang</h3>
              <p class="text-sm text-gray-400"><span x-text="cart.length"></span> item</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="{{ route('admin.pos.index') }}"
               class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium transition">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Riwayat
            </a>
            <button x-show="cart.length > 0"
                    @click="clearCart()"
                    :disabled="isProcessing"
                    style="display: none;"
                    class="flex items-center gap-1 text-sm text-red-400 hover:text-red-600 font-medium transition disabled:opacity-50">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Kosongkan
            </button>
          </div>
        </div>
      </div>

      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div x-show="cart.length === 0"
             class="flex flex-col items-center justify-center h-full text-center py-12">
          <svg class="w-16 h-16 text-gray-200 mb-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="font-semibold text-gray-400 text-base">Keranjang Kosong</p>
          <p class="text-sm text-gray-300 mt-1">Pilih produk untuk memulai</p>
        </div>
        <template x-for="item in cart"
                  :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3">
            <div :class="getItemBgColor(item.id)"
                 class="w-11 h-11 rounded-xl flex-shrink-0 flex items-center justify-center">
              <svg class="w-5 h-5 text-white"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-semibold text-gray-900 truncate"
                  x-text="item.name"></h4>
              <p class="text-xs text-gray-400 mt-0.5"
                 x-text="formatCurrency(item.price)"></p>
              <div class="flex items-center gap-2 mt-2">
                <button type="button"
                        @click="updateCartQuantity(item.id, 'decrease')"
                        :disabled="isProcessing"
                        class="w-6 h-6 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-bold text-sm disabled:opacity-50 transition">&#x2212;</button>
                <span class="text-sm font-semibold text-gray-900 w-6 text-center"
                      x-text="item.quantity"></span>
                <button type="button"
                        @click="updateCartQuantity(item.id, 'increase')"
                        :disabled="isProcessing"
                        class="w-6 h-6 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg flex items-center justify-center text-gray-700 font-bold text-sm disabled:opacity-50 transition">+</button>
              </div>
            </div>
            <button type="button"
                    @click="removeFromCart(item.id)"
                    :disabled="isProcessing"
                    class="flex-shrink-0 text-red-400 hover:text-red-600 transition disabled:opacity-50">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </template>
      </div>

      <!-- Cart Footer -->
      <div class="px-5 pb-5 pt-3 border-t border-gray-100 flex-shrink-0 space-y-3">
        <div class="flex items-center justify-between text-sm text-gray-500">
          <span>Subtotal</span>
          <span x-text="formatCurrency(cartTotal)"></span>
        </div>
        <div class="flex items-center justify-between font-bold text-gray-900">
          <span class="text-base">Total</span>
          <span class="text-base"
                x-text="formatCurrency(cartTotal)"></span>
        </div>
        <button type="button"
                @click="openCustomerTypeModal()"
                :disabled="isProcessing"
                :class="cart.length > 0 ? 'bg-gray-900 hover:bg-gray-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                class="w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition disabled:opacity-70">
          <svg class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Checkout
        </button>
      </div>
    </div>

    <!-- MODAL: Pilih Pelanggan -->
    <div x-show="showCustomerTypeModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]"
         @click.self="showCustomerTypeModal = false; bookingStep = 'type'">
      <div class="bg-white rounded-2xl w-full max-w-md mx-4 overflow-hidden shadow-xl"
           @click.stop>
        <div class="flex items-start justify-between p-6 pb-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-5 h-5 text-gray-700"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <h3 class="text-lg font-bold text-gray-900">Pilih Pelanggan</h3>
            </div>
            <p class="text-sm text-gray-500">Pilih tipe pelanggan untuk melanjutkan transaksi</p>
          </div>
          <button @click="showCustomerTypeModal = false; bookingStep = 'type'"
                  class="text-gray-400 hover:text-gray-600 transition mt-0.5">
            <svg class="w-5 h-5"
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

        <!-- Step: Type Selection -->
        <div x-show="bookingStep === 'type'"
             class="px-6 pb-6">
          <div class="grid grid-cols-2 gap-3">
            <button @click="bookingStep = 'list'"
                    class="p-5 border-2 border-gray-100 rounded-xl hover:border-blue-400 hover:bg-blue-50/50 transition group text-center">
              <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-105 transition-transform">
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
              <h4 class="font-bold text-gray-900 mb-1">Booking</h4>
              <p class="text-xs text-gray-500 mb-3">Pelanggan dengan reservasi</p>
              <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                {{ $tableSessions->count() }} Booking Aktif
              </span>
            </button>
            <button @click="selectCustomerType('walk-in')"
                    class="p-5 border-2 border-gray-100 rounded-xl hover:border-gray-300 hover:bg-gray-50 transition group text-center">
              <div class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6 text-white"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <h4 class="font-bold text-gray-900 mb-1">Walk-in</h4>
              <p class="text-xs text-gray-500 mb-3">Pelanggan tanpa reservasi</p>
              <span class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                Coming Soon
              </span>
            </button>
          </div>
        </div>

        <!-- Step: Booking List -->
        <div x-show="bookingStep === 'list'"
             style="display: none;">
          <div class="flex items-center justify-between px-6 pb-3">
            <span class="font-semibold text-gray-900">Pilih Booking</span>
            <button @click="bookingStep = 'type'"
                    class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition font-medium">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 19l-7-7 7-7" />
              </svg>
              Kembali
            </button>
          </div>
          <div class="px-6 pb-6 space-y-2 max-h-80 overflow-y-auto">
            @forelse($tableSessions as $session)
              @php
                $areaNameRaw = $session->table?->area?->name ?? 'N/A';
                $areaNameLower = strtolower($areaNameRaw);
                $avatarBg = match (true) {
                    str_contains($areaNameLower, 'room') => 'bg-blue-600',
                    str_contains($areaNameLower, 'balcon') => 'bg-violet-600',
                    str_contains($areaNameLower, 'lounge') => 'bg-cyan-600',
                    default => 'bg-gray-600',
                };
                $badgeCls = match (true) {
                    str_contains($areaNameLower, 'room') => 'bg-green-100 text-green-700',
                    str_contains($areaNameLower, 'balcon') => 'bg-violet-100 text-violet-700',
                    str_contains($areaNameLower, 'lounge') => 'bg-cyan-100 text-cyan-700',
                    default => 'bg-gray-100 text-gray-600',
                };
                $customerName = $session->customer?->name ?? 'Unknown';
                $initial = strtoupper(substr($customerName, 0, 1));
                $phone = $session->customer?->profile?->phone ?? '-';
                $tableName = $session->table?->table_number ?? 'N/A';
                $minCharge = (float) ($session->billing?->minimum_charge ?? ($session->table?->minimum_charge ?? 0));
                $ordersTotal = (float) ($session->billing?->orders_total ?? 0);
                $lifetimeSpending = (float) ($session->customer?->customerUser?->lifetime_spending ?? 0);
                $customerTier = $tiers->first(fn($t) => $t->minimum_spent <= $lifetimeSpending);
                $tierName = $customerTier?->name ?? null;
                $tierDiscount = $customerTier?->discount_percentage ?? 0;
                $sessionData = [
                    'customerId' => $session->customer_id,
                    'tableId' => $session->table_id,
                    'areaName' => $areaNameRaw,
                    'tableName' => $tableName,
                    'customerName' => $customerName,
                    'customerInitial' => $initial,
                    'customerPhone' => $phone,
                    'minimumCharge' => $minCharge,
                    'ordersTotal' => $ordersTotal,
                    'tierName' => $tierName,
                    'discountPercentage' => $tierDiscount,
                ];
              @endphp
              <button type="button"
                      @click="selectBookingSession({{ json_encode($sessionData) }})"
                      class="w-full flex items-center gap-3 p-3 rounded-xl border-2 border-transparent hover:border-blue-300 hover:bg-blue-50/50 transition text-left">
                <div class="w-10 h-10 {{ $avatarBg }} rounded-full flex-shrink-0 flex items-center justify-center">
                  <span class="text-white font-bold text-sm">{{ $initial }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-900 text-sm">{{ $customerName }}</span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeCls }}">
                      {{ $areaNameRaw }}
                    </span>
                    @if ($tierName)
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">{{ $tierName }}</span>
                    @endif
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5">{{ $phone }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    Meja {{ $tableName }}
                    @if ($minCharge > 0)
                      &bull; Min: Rp {{ number_format($minCharge, 0, ',', '.') }}
                    @endif
                  </p>
                </div>
              </button>
            @empty
              <div class="text-center py-8">
                <p class="text-sm text-gray-400">Tidak ada booking aktif saat ini</p>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Pembayaran -->
    <div x-show="showCheckoutModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]"
         @click.self="showCheckoutModal = false">
      <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-xl max-h-[90vh] overflow-y-auto"
           @click.stop>
        <!-- Header -->
        <div class="flex items-start justify-between p-6 pb-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Pembayaran</h3>
            <p class="text-sm text-gray-500 mt-0.5">Lengkapi detail pembayaran untuk menyelesaikan transaksi</p>
          </div>
          <button @click="showCheckoutModal = false"
                  class="text-gray-400 hover:text-gray-600 transition mt-0.5">
            <svg class="w-5 h-5"
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
        <form @submit.prevent="submitCheckout"
              class="px-6 pb-6 space-y-4">
          <input type="hidden"
                 x-model="checkoutForm.customer_type">
          <input type="hidden"
                 x-model="checkoutForm.customer_user_id">
          <input type="hidden"
                 x-model="checkoutForm.table_id">

          <!-- Customer Card -->
          <div class="bg-blue-600 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-white/20 rounded-full flex-shrink-0 flex items-center justify-center">
              <span class="text-white font-bold text-lg"
                    x-text="checkoutForm.customerInitial || '?'"></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-white font-bold text-base"
                      x-text="checkoutForm.customerName || 'Customer'"></span>
                <span class="px-2 py-0.5 bg-white/20 text-white text-xs font-semibold rounded-full">Booking</span>
              </div>
              <p class="text-blue-100 text-sm mt-0.5"
                 x-text="checkoutForm.customerPhone || '-'"></p>
            </div>
          </div>

          <!-- Minimum Charge -->
          <div x-show="checkoutForm.minimumCharge > 0"
               style="display: none;">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold text-gray-700">Minimum Charge</span>
              <span class="text-sm font-bold text-gray-900"
                    x-text="formatCurrency(checkoutForm.minimumCharge)"></span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2 mb-1.5">
              <div class="bg-orange-400 h-2 rounded-full transition-all"
                   :style="'width: ' + Math.min(checkoutForm.ordersTotal / checkoutForm.minimumCharge * 100, 100) + '%'"></div>
            </div>
            <p x-show="checkoutForm.ordersTotal < checkoutForm.minimumCharge"
               class="text-xs text-orange-500 font-medium"
               x-text="'Kurang ' + formatCurrency(checkoutForm.minimumCharge - checkoutForm.ordersTotal)"></p>
          </div>

          <!-- Pilih Waiter -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pilih Waiter</label>
            <select x-model="checkoutForm.waiter_id"
                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">— Pilih Waiter —</option>
              @foreach ($waiters as $waiter)
                <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
              @endforeach
            </select>
          </div>

          <!-- Metode Pembayaran -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Metode Pembayaran</label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button"
                      @click="checkoutForm.payment_method = 'cash'"
                      :class="checkoutForm.payment_method === 'cash' ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white hover:border-gray-300'"
                      class="p-3 border-2 rounded-xl text-center transition">
                <div class="text-xl mb-1">💵</div>
                <span class="text-xs font-semibold text-gray-700">Cash</span>
              </button>
              <button type="button"
                      disabled
                      class="p-3 border-2 border-gray-100 bg-gray-50 rounded-xl text-center opacity-40 cursor-not-allowed">
                <div class="text-xl mb-1">💳</div>
                <span class="text-xs font-semibold text-gray-400">Kredit</span>
              </button>
              <button type="button"
                      disabled
                      class="p-3 border-2 border-gray-100 bg-gray-50 rounded-xl text-center opacity-40 cursor-not-allowed">
                <div class="text-xl mb-1">🏦</div>
                <span class="text-xs font-semibold text-gray-400">Debit</span>
              </button>
            </div>
          </div>

          <!-- Uang Diterima (cash only) -->
          <div x-show="checkoutForm.payment_method === 'cash'"
               style="display: none;">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Uang Diterima</label>
            <input type="number"
                   x-model="checkoutForm.cash_received"
                   placeholder="Masukkan jumlah uang diterima"
                   class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>

          <!-- Summary Card -->
          <div class="bg-gray-900 rounded-2xl p-4 space-y-2.5">
            <div class="flex justify-between text-sm text-gray-300">
              <span>Subtotal</span>
              <span x-text="formatCurrency(cartTotal)"></span>
            </div>
            <div x-show="checkoutForm.discountPercentage > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-orange-400">
              <span x-text="'Diskon Tier ' + checkoutForm.tierName + ' (' + checkoutForm.discountPercentage + '%)'"></span>
              <span x-text="'-' + formatCurrency(discountAmount())"></span>
            </div>
            <div class="border-t border-gray-700 pt-2.5 flex justify-between font-bold text-white">
              <span class="text-base">Total Pembayaran</span>
              <span class="text-base"
                    x-text="formatCurrency(finalTotal())"></span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-400 pt-1">
              <span x-text="cart.length + ' item'"></span>
              <span x-text="'Poin: +' + pointsEarned()"></span>
            </div>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 pt-1">
            <button type="button"
                    @click="showCheckoutModal = false"
                    class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition">
              Batal
            </button>
            <button type="submit"
                    :disabled="isProcessing || !checkoutForm.payment_method"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <svg x-show="isProcessing"
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
              <svg x-show="!isProcessing"
                   class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7" />
              </svg>
              <span x-text="isProcessing ? 'Memproses...' : 'Selesaikan Transaksi'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast -->
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         @click="showToast = false"
         style="display: none;"
         class="fixed bottom-5 right-5 z-[70] cursor-pointer">
      <div :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
           class="px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium flex items-center gap-2">
        <svg x-show="toastType === 'success'"
             class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7" />
        </svg>
        <svg x-show="toastType === 'error'"
             class="w-4 h-4"
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

    <!-- MODAL: Cetak Struk -->
    <div x-show="showReceiptModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]"
         @click.self="closeReceiptModal()">
      <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-xl"
           @click.stop>
        <!-- Header -->
        <div class="flex items-start justify-between p-5 pb-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-gray-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-gray-900">Cetak Struk</h3>
              <p class="text-xs text-gray-500 mt-0.5">Apakah Anda ingin mencetak struk transaksi?</p>
            </div>
          </div>
          <button @click="closeReceiptModal()"
                  class="text-gray-400 hover:text-gray-600 transition mt-0.5">
            <svg class="w-5 h-5"
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

        <div class="p-5 space-y-4">
          <!-- Success Card -->
          <div class="bg-green-50 border border-green-100 rounded-2xl p-4 text-center">
            <p class="text-sm font-medium text-green-600 mb-1">Transaksi Berhasil</p>
            <p class="text-3xl font-bold text-green-700"
               x-text="receiptData?.formattedTotal || '-'"></p>
          </div>

          <!-- Transaction Details -->
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">No. Transaksi</span>
              <span class="font-semibold text-gray-800"
                    x-text="receiptData?.orderNumber"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Pelanggan</span>
              <span class="font-semibold text-gray-800"
                    x-text="receiptData?.customerName"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Meja</span>
              <span class="font-semibold text-gray-800"
                    x-text="receiptData?.tableDisplay"></span>
            </div>
            <div x-show="receiptData?.minimumCharge > 0"
                 class="flex justify-between">
              <span class="text-gray-500">Minimum Charge</span>
              <span class="font-semibold text-gray-800"
                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(receiptData?.minimumCharge || 0)"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Waktu</span>
              <span class="font-semibold text-gray-800"
                    x-text="receiptData?.printedAt"></span>
            </div>
          </div>

          <!-- Minimum Charge Warning -->
          <div x-show="receiptData && receiptData.minimumCharge > 0 && receiptData.ordersTotal < receiptData.minimumCharge"
               style="display: none;"
               class="flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-xs text-amber-700"
               x-text="'Minimum charge belum terpenuhi. Butuh tambahan Rp ' + new Intl.NumberFormat('id-ID').format((receiptData?.minimumCharge || 0) - (receiptData?.ordersTotal || 0))"></p>
          </div>

          <!-- Quick Nav Buttons -->
          <div class="grid grid-cols-3 gap-2">
            <button type="button"
                    @click="printCheckerAndNavigate('kitchen', kitchenUrl)"
                    class="flex flex-col items-center gap-1.5 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition">
              <svg class="w-5 h-5 text-orange-500"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <span class="text-xs font-semibold text-orange-600">Kitchen</span>
            </button>
            <button type="button"
                    @click="printCheckerAndNavigate('bar', barUrl)"
                    class="flex flex-col items-center gap-1.5 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition">
              <svg class="w-5 h-5 text-blue-500"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="text-xs font-semibold text-blue-600">Bar</span>
            </button>
            <a href="{{ route('admin.tables.index') }}"
               class="flex flex-col items-center gap-1.5 p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl transition">
              <svg class="w-5 h-5 text-green-500"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M3 14h18M10 4v16M14 4v16" />
              </svg>
              <span class="text-xs font-semibold text-green-600">Meja</span>
            </a>
          </div>
        </div>

        <!-- Footer Buttons -->
        <div class="flex gap-3 px-5 pb-5">
          <button type="button"
                  @click="closeReceiptModal()"
                  class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition">
            Lewati
          </button>
          <button type="button"
                  @click="printReceipt()"
                  class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak Struk
          </button>
        </div>
      </div>
    </div>

    {{-- Auth Modal for Reprint --}}
    <div x-show="showAuthModal"
         x-transition.opacity
         class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 px-4"
         @click.self="showAuthModal = false">
      <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl">
        <div class="px-6 pt-6 pb-4">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
              <svg class="h-5 w-5 text-amber-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-base font-semibold text-gray-900">Autentikasi Diperlukan</h3>
              <p class="text-xs text-gray-500">Masukkan PIN Manager untuk melanjutkan</p>
            </div>
          </div>

          <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
            Transaksi ini sudah pernah dicetak sebelumnya. Cetak ulang akan menambahkan watermark <span class="font-semibold">CETAK ULANG</span> pada struk.
          </div>

          <div class="mb-4 space-y-1.5 rounded-lg bg-gray-50 p-3 text-xs">
            <div class="flex justify-between">
              <span class="text-gray-500">Kode Berlaku</span>
              <span class="font-medium text-gray-800">{{ now()->translatedFormat('d F Y') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">No. Transaksi</span>
              <span class="font-medium text-gray-800"
                    x-text="receiptData?.orderNumber ?? '-'"></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Jenis Cetak</span>
              <span class="font-medium capitalize text-gray-800"
                    x-text="authPending?.type === 'kitchen' ? 'Kitchen' : 'Bar'"></span>
            </div>
          </div>

          <div class="mb-1">
            <label class="mb-1.5 block text-xs font-medium text-gray-700">PIN Manager (4 digit)</label>
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
             class="mb-2 text-center text-xs font-medium text-red-600"></p>
        </div>

        <div class="flex gap-2 border-t border-gray-100 px-6 pb-6 pt-4">
          <button @click="showAuthModal = false; authCode = ''; authError = ''; authPending = null;"
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
    const posRoutes = {
      selectCounter: "{{ route('admin.pos.select-counter') }}",
      addToCart: "{{ route('admin.pos.add-to-cart', '__PRODUCT_ID__') }}",
      updateCart: "{{ route('admin.pos.update-cart', '__PRODUCT_ID__') }}",
      removeFromCart: "{{ route('admin.pos.remove-from-cart', '__PRODUCT_ID__') }}",
      clearCart: "{{ route('admin.pos.clear-cart') }}",
      checkout: "{{ route('admin.pos.checkout') }}",
      verifyAuthCode: "{{ route('admin.settings.daily-auth-code.verify') }}",
    };
    const posInitialData = {
      cart: {!! json_encode($cartItems->values()) !!},
      cartTotal: {{ $cartTotal }},
      cashier: {!! json_encode(auth()->user()?->name ?? 'Admin') !!},
      currentCounter: {!! json_encode($currentCounter ?? '') !!},
      kitchenUrl: "{{ route('admin.kitchen.index') }}",
      barUrl: "{{ route('admin.bar.index') }}",
    };
  </script>

  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('posApp', () => ({
        cart: posInitialData.cart,
        cartTotal: posInitialData.cartTotal,
        isProcessing: false,
        showCustomerTypeModal: false,
        showCheckoutModal: false,
        showReceiptModal: false,
        receiptData: null,
        checkerPrinted: {
          kitchen: false,
          bar: false
        },
        showAuthModal: false,
        authCode: '',
        authError: '',
        authPending: null,
        isVerifyingAuth: false,
        cashier: posInitialData.cashier,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        counterLocation: posInitialData.currentCounter,
        kitchenUrl: posInitialData.kitchenUrl,
        barUrl: posInitialData.barUrl,
        bookingStep: 'type',
        checkoutForm: {
          customer_type: '',
          customer_user_id: '',
          customerName: '',
          customerInitial: '',
          customerPhone: '',
          table_id: '',
          table_display: '',
          waiter_id: '',
          payment_method: 'cash',
          cash_received: '',
          minimumCharge: 0,
          ordersTotal: 0,
          tierName: '',
          discountPercentage: 0,
        },

        init() {
          this.cart = posInitialData.cart;
          this.cartTotal = posInitialData.cartTotal;
        },

        formatCurrency(amount) {
          return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        },

        getItemBgColor(id) {
          const colors = ['bg-blue-500', 'bg-violet-500', 'bg-cyan-600', 'bg-orange-500', 'bg-teal-500', 'bg-pink-500'];
          const hash = String(id).split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
          return colors[hash % colors.length];
        },

        getCounterLabel() {
          const select = document.getElementById('counterLocationSelect');
          if (select && this.counterLocation) {
            const option = select.querySelector(`option[value="${this.counterLocation}"]`);
            return option ? option.textContent : this.counterLocation;
          }
          return '';
        },

        async selectCounter(event) {
          const location = event.target.value;
          try {
            const response = await fetch(posRoutes.selectCounter, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
              },
              body: JSON.stringify({
                counter_location: location
              }),
            });
            const data = await response.json();
            if (data.success) {
              this.showToastMessage('Counter location updated', 'success');
            }
          } catch (error) {
            this.showToastMessage('Failed to update counter location', 'error');
          }
        },

        async addToCart(productId) {
          if (this.isProcessing) {
            return;
          }
          this.isProcessing = true;
          try {
            const response = await fetch(
              posRoutes.addToCart.replace('__PRODUCT_ID__', productId), {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'Accept': 'application/json',
                },
              },
            );
            const data = await response.json();
            if (data.success) {
              this.cart = data.cart;
              this.cartTotal = data.cartTotal;
              this.showToastMessage(data.message, 'success');
            } else {
              this.showToastMessage(data.message || 'Gagal menambah produk', 'error');
            }
          } catch (error) {
            this.showToastMessage('Gagal menambah produk ke keranjang', 'error');
          } finally {
            this.isProcessing = false;
          }
        },

        async updateCartQuantity(productId, action) {
          if (this.isProcessing) {
            return;
          }
          this.isProcessing = true;
          try {
            const response = await fetch(
              posRoutes.updateCart.replace('__PRODUCT_ID__', productId), {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'Accept': 'application/json',
                },
                body: JSON.stringify({
                  action
                }),
              },
            );
            const data = await response.json();
            if (data.success) {
              this.cart = data.cart;
              this.cartTotal = data.cartTotal;
            } else {
              this.showToastMessage(data.message || 'Gagal mengupdate keranjang', 'error');
            }
          } catch (error) {
            this.showToastMessage('Gagal mengupdate keranjang', 'error');
          } finally {
            this.isProcessing = false;
          }
        },

        async removeFromCart(productId) {
          if (this.isProcessing) {
            return;
          }
          this.isProcessing = true;
          try {
            const response = await fetch(
              posRoutes.removeFromCart.replace('__PRODUCT_ID__', productId), {
                method: 'DELETE',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'Accept': 'application/json',
                },
              },
            );
            const data = await response.json();
            if (data.success) {
              this.cart = data.cart;
              this.cartTotal = data.cartTotal;
              this.showToastMessage(data.message, 'success');
            } else {
              this.showToastMessage(data.message || 'Gagal menghapus item', 'error');
            }
          } catch (error) {
            this.showToastMessage('Gagal menghapus item', 'error');
          } finally {
            this.isProcessing = false;
          }
        },

        async clearCart() {
          if (this.isProcessing) {
            return;
          }
          this.isProcessing = true;
          try {
            const response = await fetch(posRoutes.clearCart, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
              },
            });
            const data = await response.json();
            if (data.success) {
              this.cart = [];
              this.cartTotal = 0;
              this.showToastMessage(data.message, 'success');
            } else {
              this.showToastMessage(data.message || 'Gagal mengosongkan keranjang', 'error');
            }
          } catch (error) {
            this.showToastMessage('Gagal mengosongkan keranjang', 'error');
          } finally {
            this.isProcessing = false;
          }
        },

        openCustomerTypeModal() {
          if (this.cart.length === 0) {
            this.showToastMessage('Keranjang masih kosong!', 'error');
            return;
          }
          this.bookingStep = 'type';
          this.showCustomerTypeModal = true;
        },

        selectCustomerType(type) {
          this.checkoutForm.customer_type = type;
          this.showCustomerTypeModal = false;
          this.showCheckoutModal = true;
        },

        selectBookingSession(data) {
          this.checkoutForm.customer_type = 'booking';
          this.checkoutForm.customer_user_id = data.customerId;
          this.checkoutForm.table_id = data.tableId;
          this.checkoutForm.table_display = data.areaName + ' - Meja ' + data.tableName;
          this.checkoutForm.customerName = data.customerName;
          this.checkoutForm.customerInitial = data.customerInitial;
          this.checkoutForm.customerPhone = data.customerPhone;
          this.checkoutForm.minimumCharge = data.minimumCharge;
          this.checkoutForm.ordersTotal = data.ordersTotal;
          this.checkoutForm.tierName = data.tierName || '';
          this.checkoutForm.discountPercentage = data.discountPercentage || 0;
          this.checkoutForm.payment_method = 'cash';
          this.showCustomerTypeModal = false;
          this.bookingStep = 'type';
          this.showCheckoutModal = true;
        },

        discountAmount() {
          return Math.round(this.cartTotal * (this.checkoutForm.discountPercentage / 100));
        },

        finalTotal() {
          return this.cartTotal - this.discountAmount();
        },

        pointsEarned() {
          return Math.floor(this.finalTotal() / 10000);
        },

        async submitCheckout() {
          if (this.isProcessing) {
            return;
          }
          this.isProcessing = true;
          try {
            const response = await fetch(posRoutes.checkout, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
              },
              body: JSON.stringify({
                ...this.checkoutForm,
                discount_percentage: this.checkoutForm.discountPercentage
              }),
            });
            const data = await response.json();
            if (data.success) {
              this.receiptData = {
                orderNumber: data.order_number,
                formattedTotal: data.formatted_total,
                customerName: this.checkoutForm.customerName,
                customerInitial: this.checkoutForm.customerInitial,
                customerType: this.checkoutForm.customer_type,
                tableDisplay: this.checkoutForm.table_display,
                minimumCharge: this.checkoutForm.minimumCharge,
                ordersTotal: this.checkoutForm.ordersTotal,
                printedAt: new Date().toLocaleString('id-ID', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                }),
                items: this.cart.map(i => ({
                  ...i
                })),
              };
              this.cart = [];
              this.cartTotal = 0;
              this.showCheckoutModal = false;
              this.showReceiptModal = true;
              this.checkoutForm = {
                customer_type: '',
                customer_user_id: '',
                customerName: '',
                customerInitial: '',
                customerPhone: '',
                table_id: '',
                table_display: '',
                waiter_id: '',
                payment_method: 'cash',
                cash_received: '',
                minimumCharge: 0,
                ordersTotal: 0,
                tierName: '',
                discountPercentage: 0,
              };
            } else {
              this.showToastMessage(data.message || 'Checkout gagal', 'error');
            }
          } catch (error) {
            this.showToastMessage('Terjadi kesalahan saat checkout', 'error');
          } finally {
            this.isProcessing = false;
          }
        },

        closeReceiptModal() {
          this.showReceiptModal = false;
          this.receiptData = null;
        },

        printCheckerAndNavigate(type, url) {
          if (this.checkerPrinted[type]) {
            this.authPending = {
              type
            };
            this.authCode = '';
            this.authError = '';
            this.showAuthModal = true;
            return;
          }
          this._doPrintChecker(type, false);
        },

        async verifyAndPrint() {
          if (this.authCode.length !== 4 || this.isVerifyingAuth) {
            return;
          }
          this.isVerifyingAuth = true;
          this.authError = '';
          try {
            const response = await fetch(posRoutes.verifyAuthCode, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
              },
              body: JSON.stringify({
                code: this.authCode
              }),
            });
            const data = await response.json();
            if (data.valid) {
              const {
                type
              } = this.authPending;
              this.showAuthModal = false;
              this.authCode = '';
              this.authPending = null;
              this._doPrintChecker(type, true);
            } else {
              this.authError = 'PIN tidak valid. Periksa kembali kode harian Anda.';
            }
          } catch (e) {
            this.authError = 'Terjadi kesalahan. Coba lagi.';
          } finally {
            this.isVerifyingAuth = false;
          }
        },

        _doPrintChecker(type, isReprint) {
          const d = this.receiptData;
          const items = d ? d.items.filter(i => i.preparation_location === type) : [];
          if (!d || items.length === 0) {
            return;
          }
          this.checkerPrinted[type] = true;
          const title = type === 'kitchen' ? 'KITCHEN ORDER' : 'BAR ORDER';
          const rows = items.map(i =>
            '<tr><td style="padding:3px 0">' + i.name + '</td><td style="text-align:right;padding:3px 0"><b>' + i.quantity + '</b></td></tr>'
          ).join('');
          const css = 'body{font-family:monospace;font-size:12px;margin:0;padding:16px;}' +
            'table{width:100%;border-collapse:collapse;}' +
            '.sep{border:none;border-top:1px dashed #000;margin:8px 0;}' +
            'th{text-align:left;font-size:11px;border-bottom:1px solid #000;padding:2px 0;}' +
            'th:last-child{text-align:right;}';
          const watermarkHtml = isReprint ?
            '<p style="text-align:center;font-size:14px;font-weight:bold;color:#dc2626;border:2px solid #dc2626;padding:4px;margin-bottom:8px;letter-spacing:2px;">CETAK ULANG</p>' :
            '';
          const body = watermarkHtml +
            '<h3 style="text-align:center;margin:0 0 6px">' + title + '</h3>' +
            '<hr class="sep">' +
            '<p style="margin:2px 0">No: <b>' + d.orderNumber + '</b></p>' +
            '<p style="margin:2px 0">Tanggal: ' + d.printedAt + '</p>' +
            '<p style="margin:2px 0">Kasir: ' + this.cashier + '</p>' +
            '<hr class="sep">' +
            '<p style="margin:2px 0">Pelanggan: <b>' + d.customerName + '</b></p>' +
            '<p style="margin:2px 0">Meja: <b>' + d.tableDisplay + '</b></p>' +
            '<hr class="sep">' +
            '<table><thead><tr><th>Item</th><th style="text-align:right">Qty</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<hr class="sep">';
          const html = '<html><head><title>' + title + '</' + 'title><style>' + css + '</' + 'style></' + 'head><body>' + body + '</' + 'body></' + 'html>';
          this._printHtml(html);
        },

        printReceipt() {
          if (!this.receiptData) {
            return;
          }
          const d = this.receiptData;
          const rows = d.items.map(i =>
            '<tr><td style="padding:3px 0">' + i.name + '</td><td style="text-align:right;padding:3px 0">' + i.quantity + 'x</td><td style="text-align:right;padding:3px 0">Rp ' + new Intl.NumberFormat('id-ID').format(i.price * i.quantity) + '</td></tr>'
          ).join('');
          const css = 'body{font-family:monospace;font-size:12px;margin:0;padding:16px;}' +
            'table{width:100%;border-collapse:collapse;}' +
            '.sep{border:none;border-top:1px dashed #000;margin:8px 0;}' +
            'th{text-align:left;font-size:11px;border-bottom:1px solid #000;padding:2px 0;}';
          const body = '<h3 style="text-align:center;margin:0 0 6px">STRUK PEMBAYARAN</h3>' +
            '<hr class="sep">' +
            '<p style="margin:2px 0">No: <b>' + d.orderNumber + '</b></p>' +
            '<p style="margin:2px 0">Tanggal: ' + d.printedAt + '</p>' +
            '<p style="margin:2px 0">Kasir: ' + this.cashier + '</p>' +
            '<p style="margin:2px 0">Pelanggan: ' + d.customerName + '</p>' +
            '<p style="margin:2px 0">Meja: ' + d.tableDisplay + '</p>' +
            '<hr class="sep">' +
            '<table><thead><tr><th>Item</th><th style="text-align:right">Qty</th><th style="text-align:right">Harga</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<hr class="sep">' +
            '<p style="text-align:right;font-weight:bold">TOTAL: ' + d.formattedTotal + '</p>' +
            '<hr class="sep">' +
            '<p style="text-align:center;margin-top:8px">Terima kasih!</p>';
          const html = '<html><head><title>Struk</' + 'title><style>' + css + '</' + 'style></' + 'head><body>' + body + '</' + 'body></' + 'html>';
          this._printHtml(html);
        },

        _printHtml(html) {
          const iframe = document.createElement('iframe');
          iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:340px;height:500px;border:none;visibility:hidden;';
          document.body.appendChild(iframe);
          const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
          iframeDoc.open();
          iframeDoc.write(html);
          iframeDoc.close();
          iframe.contentWindow.focus();
          setTimeout(() => {
            iframe.contentWindow.print();
            let removed = false;
            const cleanup = () => {
              if (!removed && document.body.contains(iframe)) {
                removed = true;
                document.body.removeChild(iframe);
              }
            };
            iframe.contentWindow.onafterprint = cleanup;
            setTimeout(cleanup, 120000);
          }, 250);
        },

        showToastMessage(message, type = 'success') {
          this.toastMessage = message;
          this.toastType = type;
          this.showToast = true;
          setTimeout(() => {
            this.showToast = false;
          }, 3000);
        },
      }));
    });
  </script>
</x-app-layout>
