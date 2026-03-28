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
        <div x-show="(receiptData?.tax || 0) > 0"
             class="flex justify-between">
          <span class="text-gray-500"
                x-text="'PPN (' + (receiptData?.taxPercentage || 0) + '%)'"></span>
          <span class="font-semibold text-gray-800"
                x-text="formatCurrency(receiptData?.tax || 0)"></span>
        </div>
        <div x-show="(receiptData?.serviceCharge || 0) > 0"
             class="flex justify-between">
          <span class="text-gray-500"
                x-text="'Service Charge (' + (receiptData?.serviceChargePercentage || 0) + '%)'"></span>
          <span class="font-semibold text-gray-800"
                x-text="formatCurrency(receiptData?.serviceCharge || 0)"></span>
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
