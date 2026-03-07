<div id="tableModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
    <div class="p-6 border-b border-gray-200">
      <h3 id="modalTitle"
          class="text-xl font-bold text-gray-900">Tambah Meja</h3>
    </div>
    <form id="tableForm"
          method="POST"
          action="{{ route('admin.tables.store') }}"
          class="p-6">
      @csrf
      <input type="hidden"
             name="_method"
             value="POST"
             id="formMethod">

      <div class="grid grid-cols-2 gap-4">
        <!-- Area -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi Area <span class="text-red-500">*</span></label>
          <select name="area_id"
                  id="area_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            <option value="">Pilih Area</option>
            @foreach ($areas as $area)
              <option value="{{ $area->id }}">{{ $area->name }}</option>
            @endforeach
          </select>
        </div>

        <!-- Table Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Meja <span class="text-red-500">*</span></label>
          <input type="text"
                 name="table_number"
                 id="table_number"
                 required
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                 placeholder="Contoh: VIP-1">
        </div>

        <!-- Capacity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Kapasitas <span class="text-red-500">*</span></label>
          <input type="number"
                 name="capacity"
                 id="capacity"
                 required
                 min="1"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                 placeholder="Jumlah orang">
        </div>

        <!-- Minimum Charge -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Charge (Juta Rupiah)</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">Rp</span>
            <input type="text"
                   id="minimum_charge_display"
                   inputmode="numeric"
                   oninput="formatMinCharge(this)"
                   class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                   placeholder="0">
            <input type="hidden"
                   name="minimum_charge"
                   id="minimum_charge">
          </div>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
          <select name="status"
                  id="status"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            <option value="available">Tersedia</option>
            <option value="reserved">Reserved</option>
            <option value="occupied">Occupied</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>

        <!-- Notes -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
          <textarea name="notes"
                    id="notes"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent"
                    placeholder="Catatan tambahan (opsional)"></textarea>
        </div>

        <!-- Active Status -->
        <div class="col-span-2">
          <label class="flex items-center gap-2">
            <input type="hidden"
                   name="is_active"
                   value="0">
            <input type="checkbox"
                   name="is_active"
                   id="is_active"
                   value="1"
                   checked
                   class="w-4 h-4 text-slate-600 border-gray-300 rounded focus:ring-slate-500">
            <span class="text-sm font-medium text-gray-700">Meja Aktif</span>
          </label>
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button type="button"
                onclick="closeModal()"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
          Batal
        </button>
        <button type="submit"
                class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition">
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>
