<div id="moveTableModal"
     class="hidden fixed inset-0 bg-black/50 z-[80] flex items-center justify-center p-4"
     x-data>
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
       @click.stop>
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-gray-900">Request Pindah Meja</h3>
        <p class="text-xs text-gray-500 mt-0.5">Pilih meja tujuan untuk sesi booking aktif.</p>
      </div>
      <button type="button"
              onclick="closeMoveTableModal()"
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

    <form id="moveTableForm"
          method="POST"
          action=""
          class="px-5 py-4 space-y-4">
      @csrf

      <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
        <p class="text-xs text-amber-700">Meja saat ini</p>
        <p id="moveTableCurrentTable"
           class="text-sm font-semibold text-amber-800">-</p>
      </div>

      <div>
        <label for="moveTableTargetSelect"
               class="block text-sm font-semibold text-gray-700 mb-1">Meja tujuan</label>
        <select id="moveTableTargetSelect"
                name="new_table_id"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-300 outline-none bg-white">
          <option value="">Pilih meja tujuan</option>
          @foreach ($tables->where('is_active', true)->where('status', 'available') as $availableTable)
            <option value="{{ $availableTable->id }}">
              {{ $availableTable->table_number }} — {{ $availableTable->area->name ?? '-' }}
            </option>
          @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Hanya meja dengan status available yang ditampilkan.</p>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button type="button"
                onclick="closeMoveTableModal()"
                class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
          Batal
        </button>
        <button type="submit"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition">
          Kirim Request
        </button>
      </div>
    </form>
  </div>
</div>
