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

      <div x-show="canChooseChecker && cart.length > 0"
           style="display: none;"
           class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2">
        <p class="text-xs font-semibold text-slate-800">Target Checker</p>

        <template x-if="shouldChooseCheckerOnCheckout()">
          <div class="space-y-2">
            <p class="text-xs text-slate-700">Pilih checker tujuan sebelum checkout.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <template x-for="printer in getCheckerPrintersFromCart()"
                        :key="`checkout-checker-printer-${printer.id}`">
                <label class="inline-flex items-center gap-2 text-xs text-slate-900 bg-white border border-slate-200 rounded-lg px-3 py-2 cursor-pointer">
                  <input type="checkbox"
                         :value="String(printer.id)"
                         x-model="selectedCheckerPrinterIds"
                         class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-400">
                  <span x-text="printer.name"></span>
                </label>
              </template>
            </div>
          </div>
        </template>

        <template x-if="!shouldChooseCheckerOnCheckout()">
          <p class="text-xs text-slate-700">Order ini tidak punya lebih dari satu checker untuk dipilih.</p>
        </template>
      </div>

      <!-- Totals -->
      <div class="bg-gray-900 rounded-xl p-4 space-y-2">
        <div class="flex justify-between text-sm text-gray-300">
          <span>Subtotal</span>
          <span x-text="formatCurrency(cartTotal)"></span>
        </div>
        <div x-show="calculatedTax() > 0"
             style="display: none;"
             class="flex justify-between text-sm text-gray-300">
          <span x-text="'PPN (' + posCharges.taxPercentage + '%)'"></span>
          <span x-text="formatCurrency(calculatedTax())"></span>
        </div>
        <div x-show="calculatedServiceCharge() > 0"
             style="display: none;"
             class="flex justify-between text-sm text-gray-300">
          <span x-text="'Service Charge (' + posCharges.serviceChargePercentage + '%)'"></span>
          <span x-text="formatCurrency(calculatedServiceCharge())"></span>
        </div>
        <div class="flex justify-between text-sm text-gray-300">
          <span>Sub Total</span>
          <span x-text="formatCurrency(subTotalBeforeDiscount())"></span>
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
        <div class="border-t border-gray-700 pt-2 flex justify-between font-bold text-white">
          <span>Total Pembayaran</span>
          <span class="text-lg"
                x-text="formatCurrency(payableTotal())"></span>
        </div>
        <p class="text-xs text-gray-400 text-right"
           x-text="cart.length + ' item'"></p>
      </div>

      <template x-if="checkoutForm.customer_type === 'walk-in'">
        <div class="flex items-start gap-2.5 bg-emerald-50 border border-emerald-200 rounded-xl p-3">
          <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
          </svg>
          <p class="text-xs text-emerald-700">Khusus walk-in: PPN dan service charge dihitung terlebih dahulu, lalu diskon dipotong dari subtotal tersebut.</p>
        </div>
      </template>

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
