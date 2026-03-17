<x-app-layout>
  <div class="flex w-full h-[calc(100vh-6rem)]"
       x-data="posApp"
       x-cloak
       @walk-in-proceed.window="receiveWalkIn($event.detail)"
       @pos-toast.window="showToastMessage($event.detail.message, $event.detail.type)">

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
        <!-- Grid Size Picker -->
        <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl px-2 py-1.5">
          <span class="text-xs text-gray-400 font-medium mr-1">Grid</span>
          @foreach ([2, 3, 4, 5, 6] as $cols)
            <button type="button"
                    @click="gridCols = {{ $cols }}; localStorage.setItem('posGridCols', {{ $cols }})"
                    :class="gridCols === {{ $cols }} ? 'bg-slate-800 text-white' : 'text-gray-500 hover:bg-gray-100'"
                    class="w-7 h-7 rounded-lg text-xs font-semibold transition-colors">
              {{ $cols }}
            </button>
          @endforeach
        </div>
      </div>

      <!-- Products Grid -->
      <div class="overflow-y-auto flex-1 px-6 pb-6">
        <div class="grid gap-4"
             :style="`grid-template-columns: repeat(${gridCols}, minmax(0, 1fr))`">
          @forelse($products as $product)
            @php
              $category = strtolower($product['category'] ?? '');
              $prepLoc = $posSettings->get($product['category'] ?? '')?->preparation_location ?? 'bar';
              $isKitchen = $prepLoc === 'kitchen';
              $isItemGroup = (bool) ($product['is_item_group'] ?? false);
              $gradientClass = $isKitchen ? 'from-orange-500 to-red-600' : 'from-blue-400 to-cyan-500';
              $dotColor = $isKitchen ? 'bg-orange-400' : 'bg-blue-300';
              $outOfStock = isset($product['type']) && $product['type'] === 'item' && !$isItemGroup && ($product['stock'] ?? 0) <= 0;
              $unavailable = isset($product['type']) && $product['type'] === 'bom' && !($product['is_available'] ?? true);
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
                @if ($isItemGroup)
                  <div class="absolute bottom-2 left-2 z-10">
                    <span class="px-2 py-0.5 bg-emerald-500 text-white text-xs font-bold rounded">Item Group</span>
                  </div>
                @elseif (!$isKitchen && isset($product['stock']))
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
                  @if ($isKitchen)
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
                  <span class="text-sm font-bold text-gray-900">Rp {{ number_format($product['price'], 0, ',', '.') }}</span>
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
            <a href="JavaScript:void(0)"
               @click="openHistoryModal()"
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
              <div class="mt-2 relative">
                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs">📝</span>
                <input type="text"
                       :id="`note-${item.id}`"
                       x-model="cartNotes[item.id]"
                       placeholder="Tambah catatan item..."
                       maxlength="200"
                       class="w-full text-xs border-2 border-gray-300 rounded-lg pl-6 pr-2 py-1.5 focus:ring-2 focus:ring-gray-500 focus:border-gray-500 outline-none bg-white placeholder-gray-400 text-gray-800 font-medium" />
              </div>
              <div x-show="hasMenuAvailabilityIssue(item.id)"
                   style="display: none;"
                   class="mt-2 rounded-lg border border-red-200 bg-red-50 px-2.5 py-2">
                <p class="text-[11px] font-semibold text-red-700"
                   x-text="getMenuAvailabilityLabel(item.id)"></p>
                <p class="mt-0.5 text-[11px] text-red-600"
                   x-text="getMenuAvailabilityMessage(item.id)"></p>
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
                :disabled="isProcessing || cart.length === 0 || !canProceedToCheckout()"
                :class="cart.length > 0 && canProceedToCheckout() ? 'bg-gray-900 hover:bg-gray-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
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
            <button @click="bookingStep = 'walkin-customer'; $dispatch('walk-in-reset');"
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
              <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded-full">
                Pilih Customer
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
                    'waiterName' => $session->waiter?->profile?->name ?? ($session->waiter?->name ?? null),
                    'reservationId' => $session->table_reservation_id,
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

        <!-- Step: Walk-in Customer — managed by separate walkInCheckout Alpine component -->
        <div x-show="bookingStep === 'walkin-customer'"
             style="display: none;">
          <div x-data="walkInCheckout()"
               @walk-in-reset.window="reset()">
            <div class="flex items-center justify-between px-6 pb-3">
              <span class="font-semibold text-gray-900">Customer Walk-in</span>
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
            <div class="px-6 pb-6 space-y-3">
              <!-- Selected customer chip -->
              <div x-show="walkInSelected !== null"
                   style="display:none;"
                   class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-xl">
                <div>
                  <p class="font-semibold text-green-800 text-sm"
                     x-text="walkInSelected?.name ?? ''"></p>
                  <p class="text-xs text-green-600"
                     x-text="walkInSelected?.phone || 'Tidak ada nomor'"></p>
                </div>
                <button @click="walkInSelected = null; walkInSearch = ''"
                        class="text-green-400 hover:text-green-700 transition">
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

              <!-- Search input (when no selection and not create mode) -->
              <div x-show="walkInSelected === null && !walkInCreateMode"
                   style="display:none;"
                   class="space-y-2">
                <div class="relative">
                  <input type="text"
                         x-model="walkInSearch"
                         @input.debounce.300ms="searchWalkInCustomers()"
                         placeholder="Cari nama atau nomor HP..."
                         class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-400 focus:outline-none pr-10">
                  <svg x-show="walkInSearching"
                       class="w-4 h-4 text-gray-400 absolute right-3 top-3 animate-spin"
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
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                </div>
                <div x-show="walkInFoundCustomers.length > 0"
                     class="border border-gray-100 rounded-xl overflow-hidden">
                  <template x-for="c in walkInFoundCustomers"
                            :key="c.id">
                    <button type="button"
                            @click="selectWalkInCustomer(c)"
                            class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition text-left">
                      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center shrink-0">
                        <span class="text-xs font-bold text-gray-600"
                              x-text="c.name[0].toUpperCase()"></span>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900"
                           x-text="c.name"></p>
                        <p class="text-xs text-gray-400"
                           x-text="c.phone || 'Tidak ada nomor'"></p>
                      </div>
                    </button>
                  </template>
                </div>
                <p x-show="walkInSearch.length >= 2 && walkInFoundCustomers.length === 0 && !walkInSearching"
                   class="text-xs text-gray-400 text-center py-1">Customer tidak ditemukan.</p>
                <button type="button"
                        @click="walkInCreateMode = true; walkInNewName = walkInSearch"
                        class="w-full flex items-center justify-center gap-2 py-2.5 border-2 border-dashed border-gray-200 rounded-xl text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition font-medium">
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 4v16m8-8H4" />
                  </svg>
                  Buat Customer Baru
                </button>
              </div>

              <!-- Create new customer form -->
              <div x-show="walkInCreateMode"
                   style="display:none;"
                   class="space-y-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Nama *</label>
                  <input type="text"
                         x-model="walkInNewName"
                         placeholder="Nama customer"
                         class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-400 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">No. HP (opsional)</label>
                  <input type="tel"
                         x-model="walkInNewPhone"
                         placeholder="08xxxxxxxxxx"
                         class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-gray-400 focus:outline-none">
                </div>
                <div class="flex gap-2">
                  <button type="button"
                          @click="walkInCreateMode = false"
                          class="flex-1 py-2 border border-gray-200 rounded-xl text-sm text-gray-500 hover:bg-gray-50 transition">
                    Batal
                  </button>
                  <button type="button"
                          @click="createWalkInCustomer()"
                          :disabled="!walkInNewName.trim() || walkInCreating"
                          class="flex-1 py-2 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-gray-700 transition disabled:opacity-50">
                    <span x-show="!walkInCreating">Buat Customer</span>
                    <span x-show="walkInCreating">Membuat...</span>
                  </button>
                </div>
              </div>

              <!-- Proceed button shown when customer is selected -->
              <div x-show="walkInSelected !== null"
                   style="display:none;">
                <button type="button"
                        @click="proceedToCheckout()"
                        class="w-full py-3 bg-gray-900 text-white rounded-xl text-sm font-bold hover:bg-gray-700 transition">
                  Lanjut → Checkout
                </button>
              </div>
            </div>{{-- /x-data="walkInCheckout()" --}}
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
                <span class="px-2 py-0.5 bg-white/20 text-white text-xs font-semibold rounded-full"
                      x-text="checkoutForm.customer_type === 'walk-in' ? 'Walk-in' : 'Booking'"></span>
              </div>
              <p class="text-blue-100 text-sm mt-0.5"
                 x-text="checkoutForm.customerPhone || '-'"></p>
            </div>
          </div>

          <!-- Waiter Info (hidden for walk-in) -->
          <div x-show="checkoutForm.customer_type !== 'walk-in'"
               style="display: none;"
               class="space-y-0">
            <!-- Assigned: show chip -->
            <div x-show="checkoutForm.waiterName"
                 style="display: none;"
                 class="flex items-center gap-2.5 px-4 py-2.5 bg-indigo-50 border border-indigo-100 rounded-xl">
              <svg class="w-4 h-4 text-indigo-400 flex-shrink-0"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="text-sm text-indigo-700 font-medium"
                    x-text="'Waiter: ' + checkoutForm.waiterName"></span>
            </div>
            <!-- Not assigned + has booking: assign dropdown -->
            <div x-show="!checkoutForm.waiterName && checkoutForm.reservationId"
                 style="display: none;"
                 class="space-y-1.5">
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">Assign Waiter</label>
              <div class="flex items-center gap-2">
                <select @change="assignWaiterFromPos($event.target.value)"
                        :disabled="checkoutForm.assigningWaiter"
                        class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-400 focus:outline-none bg-white disabled:opacity-50">
                  <option value="">— Pilih Waiter —</option>
                  <template x-for="w in posWaiters"
                            :key="w.id">
                    <option :value="w.id"
                            x-text="w.name"></option>
                  </template>
                </select>
                <svg x-show="checkoutForm.assigningWaiter"
                     class="w-5 h-5 text-indigo-500 animate-spin flex-shrink-0"
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
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </div>
              <p x-show="checkoutForm.assignWaiterError"
                 x-text="checkoutForm.assignWaiterError"
                 class="text-xs text-red-500"></p>
              <p class="text-xs text-amber-600">Transaksi belum bisa diselesaikan sampai waiter dipilih.</p>
            </div>
            <!-- Not assigned + no reservation (walk-in): amber notice -->
            <div x-show="!checkoutForm.waiterName && !checkoutForm.reservationId"
                 style="display: none;"
                 class="flex items-center gap-2.5 px-4 py-2.5 bg-amber-50 border border-amber-100 rounded-xl">
              <svg class="w-4 h-4 text-amber-400 flex-shrink-0"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
              </svg>
              <span class="text-sm text-amber-700 font-medium">Waiter belum di-assign untuk sesi ini</span>
            </div>
          </div>{{-- end waiter section --}}

          <div x-show="checkoutForm.customer_type === 'walk-in'"
               style="display: none;"
               class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2">Discount (Opsional)</label>
                <div class="grid grid-cols-3 gap-2">
                  <label class="flex items-center justify-center gap-2 p-2.5 rounded-lg border cursor-pointer transition"
                         :class="checkoutForm.discount_type === 'none' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio"
                           name="walk_in_discount_type"
                           value="none"
                           class="sr-only"
                           x-model="checkoutForm.discount_type">
                    <span class="text-xs font-semibold text-gray-700">Tanpa</span>
                  </label>
                  <label class="flex items-center justify-center gap-2 p-2.5 rounded-lg border cursor-pointer transition"
                         :class="checkoutForm.discount_type === 'percentage' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio"
                           name="walk_in_discount_type"
                           value="percentage"
                           class="sr-only"
                           x-model="checkoutForm.discount_type">
                    <span class="text-xs font-semibold text-gray-700">%</span>
                  </label>
                  <label class="flex items-center justify-center gap-2 p-2.5 rounded-lg border cursor-pointer transition"
                         :class="checkoutForm.discount_type === 'nominal' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio"
                           name="walk_in_discount_type"
                           value="nominal"
                           class="sr-only"
                           x-model="checkoutForm.discount_type">
                    <span class="text-xs font-semibold text-gray-700">Nominal</span>
                  </label>
                </div>
              </div>

              <div x-show="checkoutForm.discount_type === 'percentage'"
                   style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Diskon Persentase</label>
                <input type="number"
                       min="0"
                       max="100"
                       step="0.01"
                       placeholder="Contoh: 10"
                       x-model.number="checkoutForm.discount_percentage"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
              </div>

              <div x-show="checkoutForm.discount_type === 'nominal'"
                   style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Diskon Nominal</label>
                <input type="text"
                       inputmode="numeric"
                       :value="formatCurrency(checkoutForm.discount_nominal || 0)"
                       @input="onWalkInDiscountNominalInput($event)"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
              </div>

              <div x-show="checkoutForm.discount_type !== 'none'"
                   style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Auth Code Diskon (4 digit)</label>
                <input type="password"
                       inputmode="numeric"
                       maxlength="4"
                       x-model="checkoutForm.discount_auth_code"
                       placeholder="Masukkan auth code"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-2">Mode Pembayaran</label>
              <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                       :class="checkoutForm.payment_mode === 'normal' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio"
                         name="walk_in_payment_mode"
                         value="normal"
                         class="sr-only"
                         x-model="checkoutForm.payment_mode">
                  <span class="text-xs font-semibold text-gray-700">Payment Biasa</span>
                </label>
                <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                       :class="checkoutForm.payment_mode === 'split' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio"
                         name="walk_in_payment_mode"
                         value="split"
                         class="sr-only"
                         x-model="checkoutForm.payment_mode">
                  <span class="text-xs font-semibold text-gray-700">Split Bill</span>
                </label>
              </div>
            </div>

            <div x-show="checkoutForm.payment_mode === 'normal'"
                 style="display: none;"
                 class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2">Metode Pembayaran</label>
                <div class="grid grid-cols-2 gap-2">
                  <template x-for="option in [
                    { value: 'cash', label: 'Tunai' },
                    { value: 'kredit', label: 'Kredit' },
                    { value: 'debit', label: 'Debit' },
                    { value: 'qris', label: 'QRIS' },
                    { value: 'transfer', label: 'Transfer' }
                  ]"
                            :key="option.value">
                    <label class="flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-medium cursor-pointer transition"
                           :class="checkoutForm.payment_method === option.value ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
                      <input type="radio"
                             class="sr-only"
                             name="walk_in_payment_method"
                             :value="option.value"
                             x-model="checkoutForm.payment_method">
                      <span x-text="option.label"></span>
                    </label>
                  </template>
                </div>
              </div>

              <div x-show="isWalkInNonCashNormalMode()"
                   style="display: none;">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Referensi</label>
                <input type="text"
                       x-model="checkoutForm.payment_reference_number"
                       placeholder="Nomor kartu / approval / referensi QRIS"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
              </div>
            </div>

            <div x-show="checkoutForm.payment_mode === 'split'"
                 style="display: none;"
                 class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Cash</label>
                  <input type="text"
                         inputmode="numeric"
                         :value="formatCurrency(checkoutForm.split_cash_amount || 0)"
                         @input="onWalkInSplitInput('cash', $event)"
                         class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nominal Non-Cash</label>
                  <input type="text"
                         inputmode="numeric"
                         :value="formatCurrency(checkoutForm.split_non_cash_amount || 0)"
                         @input="onWalkInSplitInput('non-cash', $event)"
                         class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Metode Non-Cash</label>
                <select x-model="checkoutForm.split_non_cash_method"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
                  <option value="debit">Debit</option>
                  <option value="kredit">Kredit</option>
                  <option value="qris">QRIS</option>
                  <option value="transfer">Transfer</option>
                  <option value="ewallet">E-Wallet</option>
                  <option value="lainnya">Lainnya</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Referensi Non-Cash</label>
                <input type="text"
                       x-model="checkoutForm.split_non_cash_reference_number"
                       placeholder="Nomor kartu / approval / referensi"
                       class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
              </div>

              <div class="rounded-xl border p-3 text-xs space-y-1"
                   :class="Math.abs(walkInSplitDiff()) > 0.01 ? 'border-red-200 bg-red-50 text-red-700' : 'border-gray-200 bg-gray-50 text-gray-700'">
                <div class="flex items-center justify-between">
                  <span>Total Split</span>
                  <span x-text="formatCurrency(walkInSplitTotal())"></span>
                </div>
                <div class="flex items-center justify-between font-semibold">
                  <span>Sisa / Selisih</span>
                  <span x-text="formatCurrency(walkInSplitDiff())"></span>
                </div>
              </div>
            </div>
          </div>

          <div x-show="hasMenuAvailabilityPreview()"
               style="display: none;"
               class="space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <div>
              <p class="text-sm font-semibold text-emerald-900">Possible Portion Menu</p>
              <p class="mt-1 text-xs text-emerald-700">Stok bahan dihitung dari detail group Accurate dan inventory saat ini.</p>
            </div>

            <div class="space-y-2">
              <template x-for="menu in menuAvailability.menu_items"
                        :key="menu.product_id">
                <div class="rounded-xl border border-emerald-200 bg-white/80 px-3 py-2.5">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-gray-900"
                         x-text="menu.name"></p>
                      <p class="mt-1 text-xs text-gray-600"
                         x-text="'Diminta ' + menu.requested_quantity + ' porsi • Possible ' + menu.possible_portions + ' porsi'"></p>
                    </div>
                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold"
                          :class="menu.is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                          x-text="menu.is_available ? 'Cukup' : 'Tidak cukup'"></span>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Summary Card -->
          <div class="bg-gray-900 rounded-2xl p-4 space-y-2.5">
            <!-- Minimum Charge row -->
            <div x-show="checkoutForm.minimumCharge > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span>Min. Charge</span>
              <span x-text="formatCurrency(checkoutForm.minimumCharge)"></span>
            </div>
            <!-- Min charge progress bar -->
            <div x-show="checkoutForm.minimumCharge > 0"
                 style="display: none;"
                 class="space-y-1">
              <div class="w-full bg-gray-700 rounded-full h-1.5">
                <div class="h-1.5 rounded-full transition-all"
                     :class="(checkoutForm.ordersTotal + cartTotal) >= checkoutForm.minimumCharge ? 'bg-green-400' : 'bg-orange-400'"
                     :style="'width: ' + Math.min((checkoutForm.ordersTotal + cartTotal) / checkoutForm.minimumCharge * 100, 100) + '%'"></div>
              </div>
              <p x-show="(checkoutForm.ordersTotal + cartTotal) < checkoutForm.minimumCharge"
                 class="text-xs text-orange-400 font-medium"
                 x-text="'Kurang ' + formatCurrency(checkoutForm.minimumCharge - (checkoutForm.ordersTotal + cartTotal)) + ' dari min. charge'"></p>
            </div>
            <div x-show="checkoutForm.minimumCharge > 0"
                 style="display: none;"
                 class="border-t border-gray-700 pt-1 flex justify-between text-sm text-gray-300">
              <span>Orders</span>
              <span x-text="formatCurrency(cartTotal)"></span>
            </div>
            <div x-show="checkoutForm.minimumCharge === 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span>Subtotal</span>
              <span x-text="formatCurrency(cartTotal)"></span>
            </div>
            <div x-show="discountAmount() > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-orange-400">
              <span x-text="checkoutForm.customer_type === 'walk-in'
                ? (checkoutForm.discount_type === 'percentage'
                  ? 'Diskon (' + getWalkInDiscountPercentage() + '%)'
                  : 'Diskon Nominal')
                : ('Diskon Tier ' + checkoutForm.tierName + ' (' + checkoutForm.discountPercentage + '%)')"></span>
              <span x-text="'-' + formatCurrency(discountAmount())"></span>
            </div>
            <div x-show="calculatedServiceCharge() > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span x-text="'Service Charge (' + posCharges.serviceChargePercentage + '%)'"></span>
              <span x-text="formatCurrency(calculatedServiceCharge())"></span>
            </div>
            <div x-show="calculatedTax() > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span x-text="'PPN (' + posCharges.taxPercentage + '%)'"></span>
              <span x-text="formatCurrency(calculatedTax())"></span>
            </div>
            <div class="border-t border-gray-700 pt-2.5 flex justify-between font-bold text-white">
              <span class="text-base">Total Tagihan Order</span>
              <span class="text-base"
                    x-text="formatCurrency(payableTotal())"></span>
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
            <button type="button"
                    @click="openConfirmModal()"
                    :disabled="!canProceedToCheckout() || requiresWaiterSelection()"
                    class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition flex items-center justify-center gap-2">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7" />
              </svg>
              Selesaikan Transaksi
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: Konfirmasi Transaksi -->
    <div x-show="showConfirmModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-[65] px-4"
         @click.self="if (!isProcessing) { showConfirmModal = false }">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden"
           @click.stop>

        <!-- Header -->
        <div class="flex items-start justify-between px-5 pt-5 pb-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-amber-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
              </svg>
            </div>
            <div>
              <h3 class="font-bold text-gray-900 text-sm"
                  x-text="'Konfirmasi Transaksi ' + (checkoutForm.customer_type === 'walk-in' ? 'Walk-in' : 'Booking')"></h3>
              <p class="text-xs text-gray-500 mt-0.5">Pastikan semua detail transaksi sudah benar sebelum melanjutkan</p>
            </div>
          </div>
          <button @click="if (!isProcessing) { showConfirmModal = false }"
                  :disabled="isProcessing"
                  class="text-gray-400 hover:text-gray-600 transition mt-0.5 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
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

        <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

          <!-- Customer Info -->
          <div class="bg-blue-50 border border-blue-100 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <tbody>
                <tr class="border-b border-blue-100">
                  <td class="px-4 py-2.5 text-gray-500 w-1/3">Pelanggan</td>
                  <td class="px-4 py-2.5 font-semibold text-gray-900 text-right"
                      x-text="checkoutForm.customerName || '-'"></td>
                </tr>
                <tr class="border-b border-blue-100">
                  <td class="px-4 py-2.5 text-gray-500">Telepon</td>
                  <td class="px-4 py-2.5 font-semibold text-gray-900 text-right"
                      x-text="checkoutForm.customerPhone || '-'"></td>
                </tr>
                <template x-if="checkoutForm.customer_type !== 'walk-in'">
                  <tr class="border-b border-blue-100">
                    <td class="px-4 py-2.5 text-gray-500">Meja</td>
                    <td class="px-4 py-2.5 font-semibold text-blue-600 text-right"
                        x-text="checkoutForm.table_display"></td>
                  </tr>
                </template>
                <template x-if="checkoutForm.customer_type !== 'walk-in'">
                  <tr>
                    <td class="px-4 py-2.5 text-gray-500">Waiter</td>
                    <td class="px-4 py-2.5 font-semibold text-gray-900 text-right"
                        x-text="checkoutForm.waiterName || 'Belum di-assign'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <!-- Items -->
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Detail Pesanan:</p>
            <div class="space-y-2">
              <template x-for="item in cart"
                        :key="item.id">
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-gray-800"
                         x-text="item.name"></p>
                      <p class="text-xs text-gray-400"
                         x-text="item.quantity + ' x ' + formatCurrency(item.price)"></p>
                    </div>
                    <span class="text-sm font-semibold text-gray-900"
                          x-text="formatCurrency(item.price * item.quantity)"></span>
                  </div>
                  <p x-show="hasMenuAvailabilityIssue(item.id)"
                     style="display: none;"
                     class="text-[11px] font-medium text-red-600"
                     x-text="getMenuAvailabilityMessage(item.id)"></p>
                </div>
              </template>
            </div>
          </div>

          <!-- Totals -->
          <div class="bg-gray-900 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm text-gray-300">
              <span>Subtotal</span>
              <span x-text="formatCurrency(cartTotal)"></span>
            </div>
            <template x-if="checkoutForm.discountPercentage > 0">
              <div class="flex justify-between text-sm text-orange-400">
                <span x-text="'Diskon Tier ' + checkoutForm.tierName + ' (' + checkoutForm.discountPercentage + '%)'"></span>
                <span x-text="'-' + formatCurrency(discountAmount())"></span>
              </div>
            </template>
            <div x-show="calculatedServiceCharge() > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span x-text="'Service Charge (' + posCharges.serviceChargePercentage + '%)'"></span>
              <span x-text="formatCurrency(calculatedServiceCharge())"></span>
            </div>
            <div x-show="calculatedTax() > 0"
                 style="display: none;"
                 class="flex justify-between text-sm text-gray-300">
              <span x-text="'PPN (' + posCharges.taxPercentage + '%)'"></span>
              <span x-text="formatCurrency(calculatedTax())"></span>
            </div>
            <div class="border-t border-gray-700 pt-2 flex justify-between font-bold text-white">
              <span>Total Pembayaran</span>
              <span class="text-lg"
                    x-text="formatCurrency(payableTotal())"></span>
            </div>
            <p class="text-xs text-gray-400 text-right"
               x-text="cart.length + ' item'"></p>
          </div>

          <!-- Booking note -->
          <template x-if="checkoutForm.customer_type !== 'walk-in'">
            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-xl p-3">
              <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
              </svg>
              <p class="text-xs text-amber-700">Transaksi booking tidak memerlukan pembayaran di tempat. Estimasi total tetap memperhitungkan flag PPN dan service charge per item.</p>
            </div>
          </template>

        </div>

        <!-- Footer -->
        <div class="flex gap-3 px-5 pb-5 pt-3 border-t border-gray-100">
          <button type="button"
                  @click="if (!isProcessing) { showConfirmModal = false }"
                  :disabled="isProcessing"
                  class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
            Kembali
          </button>
          <button type="button"
                  @click="submitCheckout()"
                  :disabled="isProcessing || !canProceedToCheckout() || requiresWaiterSelection()"
                  class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <svg x-show="isProcessing"
                 class="w-4 h-4 animate-spin"
                 fill="none"
                 viewBox="0 0 24 24"
                 style="display:none;">
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
            <span x-text="isProcessing ? 'Memproses...' : 'Ya, Selesaikan Transaksi'"></span>
          </button>
        </div>

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
            <div x-show="receiptData?.minimumCharge > 0"
                 class="flex justify-between">
              <span class="text-gray-500">Minimum Charge</span>
              <span class="font-semibold text-gray-800"
                    x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(receiptData?.minimumCharge || 0)"></span>
            </div>
            <div x-show="(receiptData?.itemsTotal || 0) > 0"
                 class="flex justify-between">
              <span class="text-gray-500">Subtotal</span>
              <span class="font-semibold text-gray-800"
                    x-text="formatCurrency(receiptData?.itemsTotal || 0)"></span>
            </div>
            <div x-show="(receiptData?.discountAmount || 0) > 0"
                 class="flex justify-between">
              <span class="text-gray-500">Diskon</span>
              <span class="font-semibold text-orange-500"
                    x-text="'-' + formatCurrency(receiptData?.discountAmount || 0)"></span>
            </div>
            <div x-show="(receiptData?.serviceCharge || 0) > 0"
                 class="flex justify-between">
              <span class="text-gray-500"
                    x-text="'Service Charge (' + (receiptData?.serviceChargePercentage || 0) + '%)'"></span>
              <span class="font-semibold text-gray-800"
                    x-text="formatCurrency(receiptData?.serviceCharge || 0)"></span>
            </div>
            <div x-show="(receiptData?.tax || 0) > 0"
                 class="flex justify-between">
              <span class="text-gray-500"
                    x-text="'PPN (' + (receiptData?.taxPercentage || 0) + '%)'"></span>
              <span class="font-semibold text-gray-800"
                    x-text="formatCurrency(receiptData?.tax || 0)"></span>
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

          <!-- Quick Nav Buttons — hanya muncul sesuai tipe printer yang relevan dengan items di order -->
          <div class="flex flex-wrap gap-2">
            <button type="button"
                    x-show="receiptData?.items?.some(i => Array.isArray(i.assigned_printer_types) && i.assigned_printer_types.includes('kitchen'))"
                    @click="printCheckerAndNavigate('kitchen', kitchenUrl)"
                    class="flex flex-1 flex-col items-center gap-1.5 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition">
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
                    x-show="receiptData?.items?.some(i => Array.isArray(i.assigned_printer_types) && i.assigned_printer_types.includes('bar'))"
                    @click="printCheckerAndNavigate('bar', barUrl)"
                    class="flex flex-1 flex-col items-center gap-1.5 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition">
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
            <button type="button"
                    x-show="(receiptData?.items?.length ?? 0) > 0"
                    @click="printCheckerAndNavigate('cashier', '')"
                    class="flex flex-1 flex-col items-center gap-1.5 p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl transition">
              <svg class="w-5 h-5 text-green-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span class="text-xs font-semibold text-green-700">Kasir</span>
            </button>
            <button type="button"
                    x-show="(receiptData?.items?.length ?? 0) > 0"
                    @click="printCheckerAndNavigate('checker', checkerUrl)"
                    class="flex flex-1 flex-col items-center gap-1.5 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
              <svg class="w-5 h-5 text-purple-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <span class="text-xs font-semibold text-purple-700">Checker</span>
            </button>
          </div>
        </div>

        <!-- Footer Buttons -->
        <div class="flex gap-3 px-5 pb-5">
          <button type="button"
                  @click="closeReceiptModal()"
                  class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition">
            Lewati
          </button>
          <!-- For Walk-in: Show Cetak Struk button -->
          <button type="button"
                  x-show="receiptData?.customerType === 'walk-in'"
                  @click="receiptData?.orderId && window.open(posRoutes.receiptBase + '/' + receiptData.orderId + '/receipt', 'struk', 'width=360,height=700,scrollbars=yes'); closeReceiptModal()"
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
          <!-- For Bookings: Show Done button -->
          <button type="button"
                  x-show="receiptData?.customerType !== 'walk-in'"
                  @click="closeReceiptModal()"
                  class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
            Done
          </button>
        </div>
      </div>
    </div>

    {{-- Auth Modal for Reprint --}}
    <div x-show="showAuthModal"
         x-transition.opacity
         style="display: none;"
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

    <script>
      const posRoutes = {
        selectCounter: "{{ route('admin.pos.select-counter') }}",
        addToCart: "{{ route('admin.pos.add-to-cart', '__PRODUCT_ID__') }}",
        updateCart: "{{ route('admin.pos.update-cart', '__PRODUCT_ID__') }}",
        removeFromCart: "{{ route('admin.pos.remove-from-cart', '__PRODUCT_ID__') }}",
        clearCart: "{{ route('admin.pos.clear-cart') }}",
        previewCheckoutAvailability: "{{ route('admin.pos.preview-checkout-availability') }}",
        checkout: "{{ route('admin.pos.checkout') }}",
        printReceiptBase: "{{ url('admin/pos/print-receipt') }}",
        verifyAuthCode: "{{ route('admin.settings.daily-auth-code.verify') }}",
        walkInSearchCustomers: "{{ route('admin.pos.walk-in.search-customers') }}",
        walkInCreateCustomer: "{{ route('admin.pos.walk-in.create-customer') }}",
        receiptBase: "{{ url('admin/pos/orders') }}",
      };
      const posAvailableTables = @json($availableTables);
      const posInitialData = {
        cart: {!! json_encode($cartItems->values()) !!},
        cartTotal: {{ $cartTotal }},
        cashier: {!! json_encode(auth()->user()?->name ?? 'Admin') !!},
        currentCounter: {!! json_encode($currentCounter ?? '') !!},
        kitchenUrl: "{{ route('admin.kitchen.index') }}",
        barUrl: "{{ route('admin.bar.index') }}",
        checkerUrl: "{{ route('admin.transaction-checker.index') }}",
      };
      const posWaiters = @json($waiters);
      const posCharges = {
        taxPercentage: {{ (float) ($generalSettings->tax_percentage ?? 0) }},
        serviceChargePercentage: {{ (float) ($generalSettings->service_charge_percentage ?? 0) }},
      };
    </script>

    {{-- Walk-in checkout: registered as a separate Alpine component. --}}
    {{-- Source of truth: resources/js/pos-walk-in.js --}}
    <script>
      document.addEventListener('alpine:init', () => {
        Alpine.data('walkInCheckout', () => ({
          walkInSearch: '',
          walkInFoundCustomers: [],
          walkInSearching: false,
          walkInSelected: null,
          walkInNewName: '',
          walkInNewPhone: '',
          walkInCreating: false,
          walkInCreateMode: false,
          walkInSelectedTable: null,

          reset() {
            this.walkInSearch = '';
            this.walkInFoundCustomers = [];
            this.walkInSearching = false;
            this.walkInSelected = null;
            this.walkInNewName = '';
            this.walkInNewPhone = '';
            this.walkInCreating = false;
            this.walkInCreateMode = false;
            this.walkInSelectedTable = null;
          },

          async searchWalkInCustomers() {
            if (this.walkInSearch.length < 2) {
              this.walkInFoundCustomers = [];
              return;
            }
            this.walkInSearching = true;
            try {
              const res = await fetch(
                posRoutes.walkInSearchCustomers + '?q=' + encodeURIComponent(this.walkInSearch), {
                  headers: {
                    Accept: 'application/json'
                  }
                },
              );
              const data = await res.json();
              this.walkInFoundCustomers = data.customers ?? [];
            } catch (e) {
              this.walkInFoundCustomers = [];
            } finally {
              this.walkInSearching = false;
            }
          },

          selectWalkInCustomer(c) {
            this.walkInSelected = c;
            this.walkInFoundCustomers = [];
            this.walkInSearch = '';
          },

          async createWalkInCustomer() {
            if (!this.walkInNewName.trim() || this.walkInCreating) {
              return;
            }
            this.walkInCreating = true;
            try {
              const res = await fetch(posRoutes.walkInCreateCustomer, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  Accept: 'application/json',
                },
                body: JSON.stringify({
                  name: this.walkInNewName,
                  phone: this.walkInNewPhone
                }),
              });
              const data = await res.json();
              if (data.success) {
                this.walkInSelected = data.customer;
                this.walkInCreateMode = false;
                this.walkInNewName = '';
                this.walkInNewPhone = '';
              } else {
                this.toast(data.message || 'Gagal membuat customer', 'error');
              }
            } catch (e) {
              this.toast('Terjadi kesalahan. Coba lagi.', 'error');
            } finally {
              this.walkInCreating = false;
            }
          },

          selectWalkInTable(t) {
            this.walkInSelectedTable = t;
          },

          proceedToCheckout() {
            if (!this.walkInSelected) {
              return;
            }
            this.$dispatch('walk-in-proceed', {
              id: this.walkInSelected.id,
              name: this.walkInSelected.name,
              phone: this.walkInSelected.phone || '',
            });
          },

          toast(message, type = 'success') {
            this.$dispatch('pos-toast', {
              message,
              type
            });
          },
        }));
      });
    </script>

    <script>
      document.addEventListener('alpine:init', () => {
        Alpine.data('posApp', () => ({
          cart: posInitialData.cart,
          cartTotal: posInitialData.cartTotal,
          isProcessing: false,
          showHistoryModal: false,
          recentOrders: [],
          historyLoading: false,
          showCustomerTypeModal: false,
          showCheckoutModal: false,
          showConfirmModal: false,
          showReceiptModal: false,
          receiptData: null,
          checkerPrinted: {
            kitchen: false,
            bar: false,
            cashier: false,
            checker: false,
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
          gridCols: parseInt(localStorage.getItem('posGridCols') ?? '4'),
          kitchenUrl: posInitialData.kitchenUrl,
          barUrl: posInitialData.barUrl,
          checkerUrl: posInitialData.checkerUrl,
          posCharges,
          bookingStep: 'type',
          checkoutForm: {
            customer_type: '',
            customer_user_id: '',
            walk_in_customer_id: '',
            payment_method: 'cash',
            payment_mode: 'normal',
            payment_reference_number: '',
            split_cash_amount: 0,
            split_non_cash_amount: 0,
            split_non_cash_method: 'debit',
            split_non_cash_reference_number: '',
            discount_type: 'none',
            discount_percentage: 0,
            discount_nominal: 0,
            discount_auth_code: '',
            customerName: '',
            customerInitial: '',
            customerPhone: '',
            table_id: '',
            table_display: '',
            waiterName: '',
            reservationId: null,
            assigningWaiter: false,
            assignWaiterError: '',
            minimumCharge: 0,
            ordersTotal: 0,
            tierName: '',
            discountPercentage: 0,
          },
          posWaiters: posWaiters,
          availableTables: posAvailableTables,
          cartNotes: {},
          menuAvailability: null,

          init() {
            this.cart = posInitialData.cart;
            this.cartTotal = posInitialData.cartTotal;

            if (this.cart.length > 0) {
              this.refreshMenuAvailability();
            }
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
                await this.refreshMenuAvailability();
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
                await this.refreshMenuAvailability();
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
                await this.refreshMenuAvailability();
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
                this.cartNotes = {};
                this.menuAvailability = null;
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

          async openCustomerTypeModal() {
            if (this.cart.length === 0) {
              this.showToastMessage('Keranjang masih kosong!', 'error');
              return;
            }

            const preview = await this.refreshMenuAvailability(true);
            if (!preview) {
              return;
            }

            this.bookingStep = 'type';
            this.showCustomerTypeModal = true;
          },

          async previewMenuAvailability(showError = true) {
            try {
              const response = await fetch(posRoutes.previewCheckoutAvailability, {
                headers: {
                  Accept: 'application/json',
                },
              });

              const data = await response.json();

              if (!response.ok || !data.success) {
                if (showError) {
                  this.showToastMessage(data.message || 'Gagal mengecek stok bahan menu.', 'error');
                }
                return null;
              }

              return data;
            } catch (error) {
              if (showError) {
                this.showToastMessage('Gagal mengecek stok bahan menu.', 'error');
              }
              return null;
            }
          },

          async refreshMenuAvailability(showError = false) {
            if (this.cart.length === 0) {
              this.menuAvailability = null;
              return null;
            }

            const preview = await this.previewMenuAvailability(showError);
            if (!preview) {
              return null;
            }

            this.menuAvailability = preview;

            return preview;
          },

          hasMenuAvailabilityPreview() {
            return Array.isArray(this.menuAvailability?.menu_items) && this.menuAvailability.menu_items.length > 0;
          },

          getMenuAvailabilityItem(productId) {
            if (!this.hasMenuAvailabilityPreview()) {
              return null;
            }

            const normalizedProductId = String(productId);

            return this.menuAvailability.menu_items.find((menuItem) => String(menuItem.product_id) === normalizedProductId) || null;
          },

          hasMenuAvailabilityIssue(productId) {
            const menuItem = this.getMenuAvailabilityItem(productId);

            return menuItem ? menuItem.is_available === false : false;
          },

          getMenuAvailabilityLabel(productId) {
            const menuItem = this.getMenuAvailabilityItem(productId);

            if (!menuItem) {
              return '';
            }

            return `Possible ${menuItem.possible_portions} porsi • Diminta ${menuItem.requested_quantity} porsi`;
          },

          getMenuAvailabilityMessage(productId) {
            const menuItem = this.getMenuAvailabilityItem(productId);

            if (!menuItem) {
              return '';
            }

            return `Stok bahan ${menuItem.name} hanya cukup ${menuItem.possible_portions} porsi.`;
          },

          canProceedToCheckout() {
            return this.menuAvailability?.can_checkout !== false;
          },

          requiresWaiterSelection() {
            return this.checkoutForm.customer_type === 'booking' && !this.checkoutForm.waiterName;
          },

          openConfirmModal() {
            if (this.requiresWaiterSelection()) {
              this.showToastMessage('Pilih waiter terlebih dahulu sebelum menyelesaikan transaksi.', 'error');
              return;
            }

            if (!this.validateWalkInPaymentFields()) {
              return;
            }

            this.showConfirmModal = true;
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
            this.checkoutForm.waiterName = data.waiterName || '';
            this.checkoutForm.reservationId = data.reservationId || null;
            this.checkoutForm.assigningWaiter = false;
            this.checkoutForm.assignWaiterError = '';
            this.showCustomerTypeModal = false;
            this.bookingStep = 'type';
            this.showCheckoutModal = true;
          },

          async assignWaiterFromPos(waiterId) {
            if (!waiterId || !this.checkoutForm.reservationId) return;
            this.checkoutForm.assigningWaiter = true;
            this.checkoutForm.assignWaiterError = '';
            try {
              const res = await fetch(`/admin/pos/assign-waiter/${this.checkoutForm.reservationId}`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  'Accept': 'application/json',
                },
                body: JSON.stringify({
                  waiter_id: waiterId
                }),
              });
              const data = await res.json();
              if (data.success) {
                this.checkoutForm.waiterName = data.waiterName;
              } else {
                this.checkoutForm.assignWaiterError = data.message || 'Gagal assign waiter.';
              }
            } catch (e) {
              this.checkoutForm.assignWaiterError = 'Terjadi kesalahan. Coba lagi.';
            } finally {
              this.checkoutForm.assigningWaiter = false;
            }
          },

          /**
           * Called when walkInCheckout component dispatches 'walk-in-proceed'.
           * Populates checkoutForm and opens the shared checkout modal.
           */
          receiveWalkIn(d) {
            this.checkoutForm.customer_type = 'walk-in';
            this.checkoutForm.walk_in_customer_id = d.id;
            this.checkoutForm.table_id = null;
            this.checkoutForm.table_display = 'Walk-in';
            this.checkoutForm.customerName = d.name;
            this.checkoutForm.customerInitial = d.name[0].toUpperCase();
            this.checkoutForm.customerPhone = d.phone;
            this.checkoutForm.minimumCharge = 0;
            this.checkoutForm.ordersTotal = 0;
            this.checkoutForm.tierName = '';
            this.checkoutForm.discountPercentage = 0;
            this.checkoutForm.waiterName = '';
            this.checkoutForm.reservationId = null;
            this.checkoutForm.payment_mode = 'normal';
            this.checkoutForm.payment_method = 'cash';
            this.checkoutForm.payment_reference_number = '';
            this.checkoutForm.split_cash_amount = 0;
            this.checkoutForm.split_non_cash_amount = this.payableTotal();
            this.checkoutForm.split_non_cash_method = 'debit';
            this.checkoutForm.split_non_cash_reference_number = '';
            this.checkoutForm.discount_type = 'none';
            this.checkoutForm.discount_percentage = 0;
            this.checkoutForm.discount_nominal = 0;
            this.checkoutForm.discount_auth_code = '';
            this.showCustomerTypeModal = false;
            this.bookingStep = 'type';
            this.showCheckoutModal = true;
          },

          getWalkInDiscountPercentage() {
            return Number(this.checkoutForm.discount_percentage || 0);
          },

          getWalkInDiscountNominal() {
            return Number(this.checkoutForm.discount_nominal || 0);
          },

          onWalkInDiscountNominalInput(event) {
            const nominal = this.extractNumber(event.target.value);
            const maxNominal = Math.max(this.cartTotal, 0);
            this.checkoutForm.discount_nominal = Math.min(Math.max(nominal, 0), maxNominal);
          },

          isWalkInNonCashNormalMode() {
            return this.checkoutForm.customer_type === 'walk-in' &&
              this.checkoutForm.payment_mode === 'normal' &&
              this.checkoutForm.payment_method !== 'cash';
          },

          walkInSplitTotal() {
            return Number(this.checkoutForm.split_cash_amount || 0) + Number(this.checkoutForm.split_non_cash_amount || 0);
          },

          walkInSplitDiff() {
            return Math.round((this.payableTotal() - this.walkInSplitTotal()) * 100) / 100;
          },

          onWalkInSplitInput(which, event) {
            const enteredAmount = this.extractNumber(event.target.value);
            const maxAmount = Math.max(this.payableTotal(), 0);
            const normalizedAmount = Math.min(Math.max(enteredAmount, 0), maxAmount);

            if (which === 'cash') {
              this.checkoutForm.split_cash_amount = normalizedAmount;
              this.checkoutForm.split_non_cash_amount = Math.max(maxAmount - normalizedAmount, 0);

              return;
            }

            this.checkoutForm.split_non_cash_amount = normalizedAmount;
            this.checkoutForm.split_cash_amount = Math.max(maxAmount - normalizedAmount, 0);
          },

          extractNumber(value) {
            const digits = String(value || '').replace(/[^0-9]/g, '');

            return digits ? Number(digits) : 0;
          },

          validateWalkInPaymentFields() {
            if (this.checkoutForm.customer_type !== 'walk-in') {
              return true;
            }

            if (this.checkoutForm.discount_type === 'percentage') {
              const discountPercentage = this.getWalkInDiscountPercentage();
              if (discountPercentage <= 0 || discountPercentage > 100) {
                this.showToastMessage('Diskon persentase harus lebih dari 0 dan maksimal 100.', 'error');

                return false;
              }
            }

            if (this.checkoutForm.discount_type === 'nominal') {
              const discountNominal = this.getWalkInDiscountNominal();
              if (discountNominal <= 0) {
                this.showToastMessage('Diskon nominal harus lebih dari 0.', 'error');

                return false;
              }
            }

            if (this.checkoutForm.discount_type !== 'none' && !/^\d{4}$/.test(String(this.checkoutForm.discount_auth_code || '').trim())) {
              this.showToastMessage('Auth code diskon harus 4 digit.', 'error');

              return false;
            }

            if (this.checkoutForm.payment_mode === 'normal') {
              if (!this.checkoutForm.payment_method) {
                this.showToastMessage('Metode pembayaran wajib dipilih.', 'error');

                return false;
              }

              if (this.isWalkInNonCashNormalMode() && !String(this.checkoutForm.payment_reference_number || '').trim()) {
                this.showToastMessage('Nomor referensi pembayaran non-cash wajib diisi.', 'error');

                return false;
              }

              return true;
            }

            const splitCashAmount = Number(this.checkoutForm.split_cash_amount || 0);
            const splitNonCashAmount = Number(this.checkoutForm.split_non_cash_amount || 0);
            const splitTotal = splitCashAmount + splitNonCashAmount;

            if (splitCashAmount <= 0 || splitNonCashAmount <= 0) {
              this.showToastMessage('Untuk split bill, nominal cash dan non-cash harus lebih dari 0.', 'error');

              return false;
            }

            if (!this.checkoutForm.split_non_cash_method) {
              this.showToastMessage('Metode non-cash untuk split bill wajib dipilih.', 'error');

              return false;
            }

            if (!String(this.checkoutForm.split_non_cash_reference_number || '').trim()) {
              this.showToastMessage('Nomor referensi non-cash untuk split bill wajib diisi.', 'error');

              return false;
            }

            if (Math.abs(splitTotal - this.payableTotal()) > 0.01) {
              this.showToastMessage('Total split (cash + non-cash) harus sama dengan grand total.', 'error');

              return false;
            }

            return true;
          },

          discountAmount() {
            if (this.checkoutForm.customer_type === 'walk-in') {
              if (this.checkoutForm.discount_type === 'percentage') {
                const amount = this.cartTotal * (this.getWalkInDiscountPercentage() / 100);

                return Math.round(amount);
              }

              if (this.checkoutForm.discount_type === 'nominal') {
                return Math.min(Math.round(this.getWalkInDiscountNominal()), Math.round(this.cartTotal));
              }

              return 0;
            }

            return Math.round(this.cartTotal * (this.checkoutForm.discountPercentage / 100));
          },

          finalTotal() {
            return this.cartTotal - this.discountAmount();
          },

          chargeableBases() {
            return this.cart.reduce((acc, item) => {
              const subtotal = Number(item.price || 0) * Number(item.quantity || 0);
              const includeTax = item.include_tax !== false;
              const includeServiceCharge = item.include_service_charge !== false;

              if (includeServiceCharge) {
                acc.serviceChargeBase += subtotal;
              }

              if (includeTax) {
                acc.taxBase += subtotal;
              }

              if (includeTax && includeServiceCharge) {
                acc.taxAndServiceBase += subtotal;
              }

              return acc;
            }, {
              serviceChargeBase: 0,
              taxBase: 0,
              taxAndServiceBase: 0,
            });
          },

          discountRatio() {
            if (this.cartTotal <= 0) {
              return 0;
            }

            return Math.min(Math.max(this.discountAmount() / this.cartTotal, 0), 1);
          },

          calculatedServiceCharge() {
            const bases = this.chargeableBases();
            const serviceChargeBaseAfterDiscount = bases.serviceChargeBase * (1 - this.discountRatio());

            return Math.round(serviceChargeBaseAfterDiscount * (this.posCharges.serviceChargePercentage / 100));
          },

          calculatedTax() {
            const bases = this.chargeableBases();
            const discountRatio = this.discountRatio();
            const taxBaseAfterDiscount = bases.taxBase * (1 - discountRatio);
            const taxAndServiceBaseAfterDiscount = bases.taxAndServiceBase * (1 - discountRatio);
            const serviceChargeTaxable = Math.round(taxAndServiceBaseAfterDiscount * (this.posCharges.serviceChargePercentage / 100));

            return Math.round((taxBaseAfterDiscount + serviceChargeTaxable) * (this.posCharges.taxPercentage / 100));
          },

          payableTotal() {
            return this.finalTotal() + this.calculatedServiceCharge() + this.calculatedTax();
          },

          pointsEarned() {
            return Math.floor(this.payableTotal() / 10000);
          },

          async submitCheckout() {
            if (this.isProcessing) {
              return;
            }

            if (this.requiresWaiterSelection()) {
              this.showToastMessage('Pilih waiter terlebih dahulu sebelum menyelesaikan transaksi.', 'error');
              return;
            }

            const preview = await this.previewMenuAvailability();
            if (!preview) {
              return;
            }

            this.menuAvailability = preview;

            if (!preview.can_checkout) {
              this.showToastMessage(preview.message || 'Stok bahan menu tidak mencukupi untuk checkout.', 'error');
              return;
            }

            if (!this.validateWalkInPaymentFields()) {
              return;
            }

            if (this.checkoutForm.customer_type === 'walk-in' && this.checkoutForm.discount_type !== 'none') {
              try {
                const verifyResponse = await fetch(posRoutes.verifyAuthCode, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  },
                  body: JSON.stringify({
                    code: String(this.checkoutForm.discount_auth_code || '').trim(),
                  }),
                });
                const verifyData = await verifyResponse.json();

                if (!verifyData.valid) {
                  this.showToastMessage('Auth code diskon tidak valid.', 'error');
                  return;
                }
              } catch (error) {
                this.showToastMessage('Gagal verifikasi auth code diskon.', 'error');
                return;
              }
            }

            this.isProcessing = true;
            try {
              const payload = {
                ...this.checkoutForm,
                cart_notes: this.cartNotes,
              };

              if (this.checkoutForm.customer_type === 'walk-in') {
                payload.discount_type = this.checkoutForm.discount_type;

                if (this.checkoutForm.discount_type === 'percentage') {
                  payload.discount_percentage = this.getWalkInDiscountPercentage();
                } else if (this.checkoutForm.discount_type === 'nominal') {
                  payload.discount_nominal = this.getWalkInDiscountNominal();
                } else {
                  payload.discount_percentage = 0;
                }
              } else {
                payload.discount_percentage = this.checkoutForm.discountPercentage;
              }

              const response = await fetch(posRoutes.checkout, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
              });
              const data = await response.json();
              if (data.success) {
                const checkoutSnapshot = {
                  ...this.checkoutForm,
                };
                const receiptItems = this.cart.map((item) => ({
                  id: item.id,
                  name: item.name,
                  quantity: Number(item.quantity || 0),
                  price: Number(item.price || 0),
                  notes: this.cartNotes[item.id] || '',
                  assigned_printer_types: Array.isArray(item.assigned_printer_types) ? item.assigned_printer_types : [],
                }));

                this.cart = [];
                this.cartTotal = 0;
                this.cartNotes = {};
                this.menuAvailability = null;
                this.showConfirmModal = false;
                this.showCheckoutModal = false;
                this.receiptData = {
                  orderId: Number(data.order_id || 0),
                  orderNumber: data.order_number || '-',
                  customerType: checkoutSnapshot.customer_type || '-',
                  customerName: checkoutSnapshot.customerName || '-',
                  tableDisplay: checkoutSnapshot.table_display || '-',
                  minimumCharge: Number(checkoutSnapshot.minimumCharge || 0),
                  ordersTotal: Number(checkoutSnapshot.ordersTotal || 0),
                  itemsTotal: Number(data.items_total || 0),
                  discountAmount: Number(data.discount_amount || 0),
                  serviceChargePercentage: Number(data.service_charge_percentage || 0),
                  serviceCharge: Number(data.service_charge || 0),
                  taxPercentage: Number(data.tax_percentage || 0),
                  tax: Number(data.tax || 0),
                  total: Number(data.total || 0),
                  formattedTotal: data.formatted_total || this.formatCurrency(Number(data.total || 0)),
                  receiptPrinted: Boolean(data.receipt_printed),
                  printedAt: new Date().toLocaleString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                  }),
                  items: receiptItems,
                };
                this.checkerPrinted = {
                  kitchen: false,
                  bar: false,
                  cashier: false,
                  checker: false,
                };
                this.showReceiptModal = true;
                this.showToastMessage(data.message || 'Checkout berhasil.', 'success');

                this.checkoutForm = {
                  customer_type: '',
                  customer_user_id: '',
                  walk_in_customer_id: '',
                  payment_method: 'cash',
                  payment_mode: 'normal',
                  payment_reference_number: '',
                  split_cash_amount: 0,
                  split_non_cash_amount: 0,
                  split_non_cash_method: 'debit',
                  split_non_cash_reference_number: '',
                  discount_type: 'none',
                  discount_percentage: 0,
                  discount_nominal: 0,
                  discount_auth_code: '',
                  customerName: '',
                  customerInitial: '',
                  customerPhone: '',
                  table_id: '',
                  table_display: '',
                  waiterName: '',
                  reservationId: null,
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

            // Kasir → delegate to full receipt print
            if (type === 'cashier') {
              this.checkerPrinted[type] = true;
              this.printReceipt();
              return;
            }

            // Checker → print ALL items; Kitchen/Bar → only assigned_printer_types
            const items = d ?
              (type === 'checker' ?
                d.items :
                d.items.filter(i =>
                  Array.isArray(i.assigned_printer_types) && i.assigned_printer_types.includes(type),
                )) : [];

            if (!d || items.length === 0) {
              return;
            }
            this.checkerPrinted[type] = true;
            const titleMap = {
              kitchen: 'KITCHEN ORDER',
              bar: 'BAR ORDER',
              checker: 'ORDER CHECKER'
            };
            const title = titleMap[type] ?? type.toUpperCase();
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
              '&': '&amp;',
              '<': '&lt;',
              '>': '&gt;',
              '"': '&quot;',
              "'": '&#39;',
            } [char]));
            const rows = items.map(i => {
              const noteText = String(i.notes || '').trim();
              const noteRow = noteText ?
                '<tr><td colspan="2" style="padding:0 0 4px 10px;font-weight:700;font-size:12px">NOTE: ' + escapeHtml(noteText) + '</td></tr>' :
                '';

              return '<tr><td style="padding:4px 0;font-weight:700;font-size:13px">' + escapeHtml(i.name) + '</td><td style="text-align:right;padding:4px 0;font-weight:700;font-size:13px"><b>' + i.quantity + '</b></td></tr>' + noteRow;
            }).join('');
            const css = 'body{font-family:Arial,monospace;font-size:13px;font-weight:600;margin:0;padding:16px;}' +
              'table{width:100%;border-collapse:collapse;}' +
              '.sep{border:none;border-top:1px dashed #000;margin:8px 0;}' +
              'th{text-align:left;font-size:12px;font-weight:700;border-bottom:1px solid #000;padding:2px 0;}' +
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

          async printReceipt() {
            if (!this.receiptData) {
              return;
            }

            const orderId = Number(this.receiptData.orderId || 0);

            if (orderId > 0) {
              try {
                const response = await fetch(`${posRoutes.printReceiptBase}/${orderId}`, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    Accept: 'application/json',
                  },
                });

                const data = await response.json();

                if (response.ok && data.success) {
                  this.showToastMessage('Struk berhasil dikirim ke printer.', 'success');

                  return;
                }

                this.showToastMessage(data.message || 'Gagal kirim ke printer server. Menampilkan print browser sebagai cadangan.', 'error');
              } catch (error) {
                this.showToastMessage('Gagal kirim ke printer server. Menampilkan print browser sebagai cadangan.', 'error');
              }
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
              ((d.itemsTotal || 0) > 0 ? '<p style="text-align:right;margin:2px 0">Subtotal: ' + this.formatCurrency(d.itemsTotal || 0) + '</p>' : '') +
              ((d.discountAmount || 0) > 0 ? '<p style="text-align:right;margin:2px 0">Diskon: -' + this.formatCurrency(d.discountAmount || 0) + '</p>' : '') +
              ((d.serviceCharge || 0) > 0 ? '<p style="text-align:right;margin:2px 0">Service Charge (' + (d.serviceChargePercentage || 0) + '%): ' + this.formatCurrency(d.serviceCharge || 0) + '</p>' : '') +
              ((d.tax || 0) > 0 ? '<p style="text-align:right;margin:2px 0">PPN (' + (d.taxPercentage || 0) + '%): ' + this.formatCurrency(d.tax || 0) + '</p>' : '') +
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

          async openHistoryModal() {
            this.showHistoryModal = true;
            this.historyLoading = true;
            try {
              const res = await fetch('{{ route('admin.pos.recent-orders') }}', {
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                },
              });
              const data = await res.json();
              this.recentOrders = data.orders ?? [];
            } catch (e) {
              this.showToastMessage('Gagal memuat riwayat transaksi.', 'error');
              this.showHistoryModal = false;
            } finally {
              this.historyLoading = false;
            }
          },

          formatHistoryCurrency(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
          },
        }));
      });
    </script>
    <!-- History Modal -->
    <div x-show="showHistoryModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
           @click="showHistoryModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[85vh]">

        <!-- Header -->
        <div class="flex items-start justify-between px-6 pt-6 pb-4 flex-shrink-0">
          <div>
            <h2 class="text-lg font-bold text-gray-900">&#128221; Transaksi Terakhir</h2>
            <p class="text-xs text-gray-400 mt-0.5">Klik transaksi untuk mencetak ulang</p>
          </div>
          <button @click="showHistoryModal = false"
                  class="text-gray-400 hover:text-gray-600 transition">
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

        <!-- Loading -->
        <div x-show="historyLoading"
             class="flex items-center justify-center py-12">
          <svg class="w-7 h-7 text-gray-400 animate-spin"
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
                  d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
        </div>

        <!-- Order List -->
        <div x-show="!historyLoading"
             class="overflow-y-auto flex-1 px-4 pb-2 space-y-2">
          <template x-if="recentOrders.length === 0">
            <p class="text-center text-sm text-gray-400 py-10">Belum ada transaksi.</p>
          </template>
          <template x-for="order in recentOrders"
                    :key="order.id">
            <div class="flex items-start gap-3 p-4 rounded-xl border border-gray-100 hover:bg-gray-50 cursor-pointer transition">
              <!-- Icon -->
              <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-green-600"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="text-sm font-bold text-gray-900"
                        x-text="order.order_number"></span>
                  <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-700"
                        x-text="order.type"></span>
                </div>
                <p class="text-xs text-gray-400 mb-2"
                   x-text="order.ordered_at"></p>
                <div class="flex items-center gap-1 mb-0.5">
                  <svg class="w-3 h-3 text-blue-400 flex-shrink-0"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="text-xs text-blue-600 font-medium truncate"
                        x-text="order.customer_name"></span>
                </div>
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3 text-gray-400 flex-shrink-0"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-xs text-gray-500 truncate"
                        x-text="order.area + ' - Meja ' + order.table"></span>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                  <span class="text-xs text-gray-400"
                        x-text="order.items_count + ' item'"></span>
                  <span class="text-sm font-bold text-gray-900"
                        x-text="formatHistoryCurrency(order.total)"></span>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-4 flex-shrink-0 border-t border-gray-100">
          <button @click="showHistoryModal = false"
                  class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
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

  </div>{{-- /x-data="posApp" --}}

</x-app-layout>
