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
