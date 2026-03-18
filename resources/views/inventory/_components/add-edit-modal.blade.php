<div id="itemModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
    <div class="p-6 border-b border-gray-200">
      <h3 id="modalTitle"
          class="text-xl font-bold text-gray-900">Tambah Produk</h3>
    </div>
    <form id="itemForm"
          method="POST"
          action="{{ route('admin.inventory.store') }}"
          class="p-6">
      @csrf
      <input type="hidden"
             name="_method"
             value="POST"
             id="formMethod">

      <div class="grid grid-cols-2 gap-4">
        <!-- Name -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk <span class="text-red-500">*</span></label>
          <input type="text"
                 name="name"
                 id="name"
                 required
                 placeholder="Contoh: Black Pepper, Olive Oil"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>

        <!-- Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Kode Produk <span class="text-red-500">*</span></label>
          <input type="text"
                 name="code"
                 id="code"
                 required
                 placeholder="Contoh: BP001"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>

        <!-- Category Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Kategori <span class="text-red-500">*</span></label>
          <select name="category_type"
                  id="category_type"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            <option value="">Pilih Kategori</option>
            @foreach ($categoryTypes as $type)
              <option value="{{ $type }}">{{ ucfirst($type) }}</option>
            @endforeach
          </select>
        </div>

        <!-- Unit -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Satuan <span class="text-red-500">*</span></label>
          <select name="unit"
                  id="unit"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            <option value="unit">Unit</option>
            <option value="bottle">Bottle</option>
            <option value="kg">Kg</option>
            <option value="liter">Liter</option>
            <option value="pack">Pack</option>
          </select>
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Harga <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
            <input type="number"
                   name="price"
                   id="price"
                   required
                   min="0"
                   step="100"
                   placeholder="0"
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
          </div>
        </div>

        <!-- Stock Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Stok <span class="text-red-500">*</span></label>
          <input type="number"
                 name="stock_quantity"
                 id="stock_quantity"
                 required
                 min="0"
                 placeholder="0"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>

        <!-- Threshold -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Threshold (Batas Minimum) <span class="text-red-500">*</span></label>
          <input type="number"
                 name="threshold"
                 id="threshold"
                 required
                 min="0"
                 placeholder="10"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>

        <!-- Is Active -->
        <div class="col-span-2 flex items-center gap-3">
          <input type="checkbox"
                 name="is_active"
                 id="is_active"
                 value="1"
                 class="w-4 h-4 text-slate-800 border-gray-300 rounded focus:ring-slate-500">
          <label for="is_active"
                 class="text-sm font-medium text-gray-700">Produk Aktif</label>
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
