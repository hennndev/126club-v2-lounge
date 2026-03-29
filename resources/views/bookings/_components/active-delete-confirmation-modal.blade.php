<div id="activeDeleteModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
    <div class="p-6 space-y-4">
      <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
        <svg class="w-6 h-6 text-red-600"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>

      <div class="text-center">
        <h3 class="text-lg font-bold text-gray-900">Hapus Active Table</h3>
        <p class="text-sm text-gray-500 mt-1">
          Aksi ini akan menghapus active session dan booking, lalu mengembalikan meja ke status available.
        </p>
      </div>

      <form id="activeDeleteForm"
            method="POST"
            class="space-y-3">
        @csrf
        @method('DELETE')

        <div>
          <label for="activeDeleteConfirmOne"
                 class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi 1/2 — Ketik <span class="text-red-600">HAPUS</span></label>
          <input id="activeDeleteConfirmOne"
                 type="text"
                 autocomplete="off"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                 placeholder="HAPUS">
        </div>

        <div>
          <label for="activeDeleteConfirmTwo"
                 class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi 2/2 — Ketik nomor meja: <span id="activeDeleteTableLabel"
                  class="text-red-600">-</span></label>
          <input id="activeDeleteConfirmTwo"
                 type="text"
                 autocomplete="off"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                 placeholder="Nomor meja">
        </div>

        <div class="flex gap-3 pt-1">
          <button type="button"
                  onclick="closeActiveDeleteModal()"
                  class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Batal
          </button>
          <button id="activeDeleteSubmitBtn"
                  type="submit"
                  disabled
                  class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50 transition">
            Hapus Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
