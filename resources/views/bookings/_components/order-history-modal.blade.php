<div id="orderHistoryModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center p-4 pt-16 overflow-y-auto">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mb-8">
    <div class="flex items-center justify-between p-5 border-b border-gray-200">
      <div>
        <h3 class="text-lg font-bold text-gray-900"
            id="orderHistoryTitle">Riwayat Order</h3>
        <p class="text-xs text-gray-400 mt-0.5">Daftar semua order dalam sesi ini</p>
      </div>
      <button onclick="closeOrderHistoryModal()"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
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
    <div class="p-5 max-h-[60vh] overflow-y-auto"
         id="orderHistoryBody">
      {{-- Populated by JS --}}
    </div>
  </div>
</div>

<div id="moveOrderModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
    <div class="flex items-center justify-between p-5 border-b border-gray-200">
      <div>
        <h3 class="text-base font-bold text-gray-900">Pindah Order</h3>
        <p class="text-xs text-gray-400 mt-0.5">Pindahkan order ke sesi aktif lain</p>
      </div>
      <button onclick="closeMoveOrderModal()"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
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

    <form id="moveOrderForm"
          method="POST"
          class="p-5 space-y-4">
      @csrf
      <input type="hidden"
             id="moveOrderId"
             name="order_id"
             value="">

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Sesi Asal</label>
        <p id="moveOrderSourceInfo"
           class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">-</p>
      </div>

      <div>
        <label for="moveOrderTargetSessionId"
               class="block text-xs font-semibold text-gray-500 mb-1">Sesi Tujuan</label>
        <select id="moveOrderTargetSessionId"
                name="target_table_session_id"
                required
                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
          <option value="">Pilih sesi tujuan</option>
        </select>
      </div>

      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button"
                onclick="closeMoveOrderModal()"
                class="px-3 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
          Batal
        </button>
        <button type="submit"
                class="px-3 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition">
          Pindahkan
        </button>
      </div>
    </form>
  </div>
</div>

<div id="cancelOrderModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
    <div class="flex items-center justify-between p-5 border-b border-gray-200">
      <div>
        <h3 class="text-base font-bold text-gray-900">Batalkan Order Pending</h3>
        <p class="text-xs text-gray-400 mt-0.5">Wajib verifikasi daily auth code</p>
      </div>
      <button onclick="closeCancelOrderModal()"
              class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
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

    <form id="cancelOrderForm"
          method="POST"
          class="p-5 space-y-4">
      @csrf
      <input type="hidden"
             id="cancelOrderId"
             name="order_id"
             value="">

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">Order</label>
        <p id="cancelOrderNumber"
           class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">-</p>
      </div>

      <div>
        <label for="cancelOrderAuthCode"
               class="block text-xs font-semibold text-gray-500 mb-1">Daily Auth Code</label>
        <input id="cancelOrderAuthCode"
               name="cancel_auth_code"
               type="password"
               inputmode="numeric"
               maxlength="4"
               required
               class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm"
               placeholder="Masukkan auth code harian">
      </div>

      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button"
                onclick="closeCancelOrderModal()"
                class="px-3 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
          Batal
        </button>
        <button type="submit"
                class="px-3 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
          Batalkan Order
        </button>
      </div>
    </form>
  </div>
</div>
