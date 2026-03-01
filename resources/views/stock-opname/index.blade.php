<x-app-layout>
  <div class="p-6"
       x-data="stockOpname()"
       x-init="init()">

    <!-- Flash Messages -->
    <div x-show="flashMessage"
         x-transition
         style="display: none;"
         :class="flashSuccess ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
         class="mb-4 px-4 py-3 border rounded-lg flex items-center justify-between">
      <span x-text="flashMessage"></span>
      <button @click="flashMessage = ''"
              class="ml-4 text-lg font-bold leading-none">&times;</button>
    </div>

    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Opname</h1>
        <p class="text-sm text-gray-500">Hitung dan sesuaikan stok inventaris</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap justify-end">
        <button @click="resetForm()"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Reset
        </button>
        <button @click="showPrintModal = true"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print Form
        </button>
        <a href="{{ route('admin.stock-opname.history') }}"
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
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
      </div>
    </div>

    <!-- Form Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
      <h2 class="text-base font-semibold text-gray-700 mb-4">Informasi Opname</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Opname <span class="text-red-500">*</span></label>
          <input type="date"
                 x-model="form.opname_date"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Petugas <span class="text-red-500">*</span></label>
          <input type="text"
                 x-model="form.officer_name"
                 placeholder="Nama petugas yang melakukan opname"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
          <input type="text"
                 x-model="form.notes"
                 placeholder="Catatan opsional"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none" />
        </div>
      </div>
      <div x-show="currentId"
           style="display: none;"
           class="mt-3">
        <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">
          <svg class="w-3 h-3"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
          Mengedit draft #<span x-text="currentId"></span>
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500 mb-1">Total Produk</p>
        <p class="text-2xl font-bold text-gray-800"
           x-text="items.length"></p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500 mb-1">Sudah Dihitung</p>
        <p class="text-2xl font-bold text-teal-600"
           x-text="items.filter(i => i.physical_stock !== '' && i.physical_stock !== null).length"></p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500 mb-1">Ada Selisih</p>
        <p class="text-2xl font-bold text-orange-500"
           x-text="items.filter(i => i.physical_stock !== '' && i.physical_stock !== null && parseInt(i.physical_stock) !== i.system_stock).length"></p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs text-gray-500 mb-1">Total Selisih</p>
        <p class="text-2xl font-bold text-red-600"
           x-text="items.reduce((sum, i) => sum + (i.physical_stock !== '' && i.physical_stock !== null ? Math.abs(parseInt(i.physical_stock) - i.system_stock) : 0), 0)"></p>
      </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- Filters -->
      <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input type="text"
                 x-model="search"
                 placeholder="Cari produk..."
                 class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none" />
        </div>
        <select x-model="categoryFilter"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none">
          <option value="">Semua Kategori</option>
          @foreach ($categoryTypes as $cat)
            <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
          @endforeach
        </select>
        <button @click="clearAllPhysical()"
                class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition whitespace-nowrap">
          Hapus Semua Input
        </button>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left w-10">No</th>
              <th class="px-4 py-3 text-left">Produk</th>
              <th class="px-4 py-3 text-left">Kategori</th>
              <th class="px-4 py-3 text-center">Satuan</th>
              <th class="px-4 py-3 text-center">Stock Sistem</th>
              <th class="px-4 py-3 text-center w-36">Stock Fisik</th>
              <th class="px-4 py-3 text-center w-24">Selisih</th>
              <th class="px-4 py-3 text-left w-48">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="(item, index) in filteredItems()"
                      :key="item.inventory_item_id">
              <tr class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-500"
                    x-text="index + 1"></td>
                <td class="px-4 py-3 font-medium text-gray-900"
                    x-text="item.name"></td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="categoryClass(item.category_type)"
                        x-text="item.category_type ? item.category_type.charAt(0).toUpperCase() + item.category_type.slice(1) : '-'">
                  </span>
                </td>
                <td class="px-4 py-3 text-center text-gray-600"
                    x-text="item.unit"></td>
                <td class="px-4 py-3 text-center font-mono text-gray-700"
                    x-text="item.system_stock"></td>
                <td class="px-4 py-3">
                  <input type="number"
                         x-model="item.physical_stock"
                         min="0"
                         placeholder="—"
                         class="w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none font-mono" />
                </td>
                <td class="px-4 py-3 text-center font-mono font-semibold">
                  <template x-if="item.physical_stock !== '' && item.physical_stock !== null">
                    <span :class="getDifference(item) > 0 ? 'text-green-600' : getDifference(item) < 0 ? 'text-red-600' : 'text-gray-500'"
                          x-text="(getDifference(item) > 0 ? '+' : '') + getDifference(item)">
                    </span>
                  </template>
                  <template x-if="item.physical_stock === '' || item.physical_stock === null">
                    <span class="text-gray-300">—</span>
                  </template>
                </td>
                <td class="px-4 py-3">
                  <input type="text"
                         x-model="item.item_notes"
                         placeholder="Catatan..."
                         class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 outline-none" />
                </td>
              </tr>
            </template>
            <tr x-show="filteredItems().length === 0">
              <td colspan="8"
                  class="px-4 py-8 text-center text-gray-400">Tidak ada produk ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Actions -->
      <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-sm text-gray-500">
          Menampilkan <span class="font-medium"
                x-text="filteredItems().length"></span> dari <span class="font-medium"
                x-text="items.length"></span> produk
        </p>
        <div class="flex gap-3">
          <button @click="saveDraft()"
                  :disabled="saving"
                  class="px-5 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-medium disabled:opacity-50 flex items-center gap-2">
            <svg x-show="saving"
                 class="animate-spin w-4 h-4"
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
            <span x-text="saving ? 'Menyimpan...' : 'Simpan Draft'"></span>
          </button>
          <button @click="completeOpname()"
                  :disabled="saving"
                  class="px-5 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition font-medium disabled:opacity-50 flex items-center gap-2">
            <svg x-show="saving"
                 class="animate-spin w-4 h-4"
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
            <svg x-show="!saving"
                 class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="saving ? 'Memproses...' : 'Selesai & Adjust Stock'"></span>
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Complete Modal -->
    <div x-show="showConfirmModal"
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div x-transition.scale
           @click.stop
           class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Selesaikan Stock Opname?</h3>
        <p class="text-sm text-gray-600 mb-2">
          Tindakan ini akan menyesuaikan stok sistem dengan stok fisik yang sudah diisi.
          Stok yang belum diisi tidak akan berubah.
        </p>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4 text-sm text-orange-700">
          <strong>Produk dengan input:</strong>
          <span x-text="items.filter(i => i.physical_stock !== '' && i.physical_stock !== null).length"></span>
          dari <span x-text="items.length"></span> produk.
          Tindakan ini tidak dapat dibatalkan.
        </div>
        <div class="flex gap-3 justify-end">
          <button @click="showConfirmModal = false"
                  class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Batal</button>
          <button @click="doComplete()"
                  class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition font-medium">Ya, Selesaikan</button>
        </div>
      </div>
    </div>

    <!-- Print Form Modal -->
    <div x-show="showPrintModal"
         x-transition.opacity
         style="display: none;"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div @click.stop
           class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <h3 class="text-lg font-bold text-gray-900">Print Form Stock Opname</h3>
          <div class="flex gap-2">
            <button @click="printForm()"
                    class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition text-sm font-medium flex items-center gap-2">
              <svg class="w-4 h-4"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Cetak
            </button>
            <button @click="showPrintModal = false"
                    class="px-4 py-2 bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300 transition text-sm">Tutup</button>
          </div>
        </div>
        <div class="overflow-y-auto flex-1 p-6"
             id="print-form-content">
          <!-- Print Header -->
          <div class="text-center mb-6">
            <h2 class="text-2xl font-bold tracking-widest">126 CLUB</h2>
            <h3 class="text-lg font-semibold mt-1">FORM STOCK OPNAME</h3>
            <div class="border-t-2 border-b-2 border-gray-800 py-2 mt-2 grid grid-cols-3 text-sm gap-4 text-left">
              <div>
                <span class="font-medium">Tanggal:</span>
                <span x-text="form.opname_date || '_______________'"></span>
              </div>
              <div>
                <span class="font-medium">Petugas:</span>
                <span x-text="form.officer_name || '_______________'"></span>
              </div>
              <div>
                <span class="font-medium">Catatan:</span>
                <span x-text="form.notes || '_______________'"></span>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Waktu Cetak: <span x-text="new Date().toLocaleString('id-ID')"></span></p>
          </div>

          <!-- Print Table grouped by category -->
          <template x-for="cat in uniqueCategories()"
                    :key="cat">
            <div class="mb-4">
              <h4 class="font-semibold text-sm uppercase tracking-wider bg-gray-100 px-3 py-1.5 mb-0"
                  x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></h4>
              <table class="w-full text-xs border-collapse">
                <thead>
                  <tr class="border border-gray-300">
                    <th class="border border-gray-300 px-2 py-1 text-left w-8">No</th>
                    <th class="border border-gray-300 px-2 py-1 text-left">Nama Produk</th>
                    <th class="border border-gray-300 px-2 py-1 text-center w-16">Satuan</th>
                    <th class="border border-gray-300 px-2 py-1 text-center w-20">Stok Sistem</th>
                    <th class="border border-gray-300 px-2 py-1 text-center w-24">Stok Fisik</th>
                    <th class="border border-gray-300 px-2 py-1 text-center w-20">Selisih</th>
                    <th class="border border-gray-300 px-2 py-1 text-left w-36">Catatan</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="(item, idx) in itemsByCategory(cat)"
                            :key="item.inventory_item_id">
                    <tr class="border border-gray-300">
                      <td class="border border-gray-300 px-2 py-1"
                          x-text="idx + 1"></td>
                      <td class="border border-gray-300 px-2 py-1 font-medium"
                          x-text="item.name"></td>
                      <td class="border border-gray-300 px-2 py-1 text-center"
                          x-text="item.unit"></td>
                      <td class="border border-gray-300 px-2 py-1 text-center"
                          x-text="item.system_stock"></td>
                      <td class="border border-gray-300 px-2 py-1 text-center"></td>
                      <td class="border border-gray-300 px-2 py-1 text-center"></td>
                      <td class="border border-gray-300 px-2 py-1"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </template>

          <div class="mt-8 grid grid-cols-3 gap-8 text-sm text-center">
            <div>
              <p class="font-medium mb-8">Dibuat Oleh</p>
              <div class="border-t border-gray-400 pt-1">
                <span x-text="form.officer_name || '_______________'"></span>
              </div>
            </div>
            <div>
              <p class="font-medium mb-8">Diperiksa Oleh</p>
              <div class="border-t border-gray-400 pt-1">_______________</div>
            </div>
            <div>
              <p class="font-medium mb-8">Disetujui Oleh</p>
              <div class="border-t border-gray-400 pt-1">_______________</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  @push('styles')
    <style>
      @media print {
        body * {
          visibility: hidden;
        }

        #print-form-content,
        #print-form-content * {
          visibility: visible;
        }

        #print-form-content {
          position: fixed;
          left: 0;
          top: 0;
          width: 100%;
        }
      }
    </style>
  @endpush

  @push('scripts')
    <script>
      function stockOpname() {
        return {
          form: {
            opname_date: '{{ now()->format('Y-m-d') }}',
            officer_name: '',
            notes: '',
          },
          items: @json($itemsData),
          search: '',
          categoryFilter: '',
          saving: false,
          currentId: null,
          showConfirmModal: false,
          showPrintModal: false,
          flashMessage: '',
          flashSuccess: true,

          init() {},

          filteredItems() {
            return this.items.filter(item => {
              const matchSearch = !this.search ||
                item.name.toLowerCase().includes(this.search.toLowerCase()) ||
                (item.category_type && item.category_type.toLowerCase().includes(this.search.toLowerCase()));
              const matchCategory = !this.categoryFilter || item.category_type === this.categoryFilter;
              return matchSearch && matchCategory;
            });
          },

          getDifference(item) {
            if (item.physical_stock === '' || item.physical_stock === null) {
              return 0;
            }
            return parseInt(item.physical_stock) - item.system_stock;
          },

          categoryClass(type) {
            const map = {
              'spices': 'bg-orange-100 text-orange-700',
              'spirits': 'bg-purple-100 text-purple-700',
              'beverage': 'bg-blue-100 text-blue-700',
              'dairy': 'bg-green-100 text-green-700',
              'condiments': 'bg-yellow-100 text-yellow-700',
            };
            return map[type] || 'bg-gray-100 text-gray-700';
          },

          uniqueCategories() {
            return [...new Set(this.items.map(i => i.category_type).filter(Boolean))].sort();
          },

          itemsByCategory(cat) {
            return this.items.filter(i => i.category_type === cat);
          },

          clearAllPhysical() {
            this.items.forEach(item => {
              item.physical_stock = '';
              item.item_notes = '';
            });
          },

          resetForm() {
            this.form = {
              opname_date: '{{ now()->format('Y-m-d') }}',
              officer_name: '',
              notes: '',
            };
            this.currentId = null;
            this.clearAllPhysical();
            this.search = '';
            this.categoryFilter = '';
          },

          buildPayload() {
            return {
              opname_date: this.form.opname_date,
              officer_name: this.form.officer_name,
              notes: this.form.notes,
              items: this.items.map(item => ({
                inventory_item_id: item.inventory_item_id,
                system_stock: item.system_stock,
                physical_stock: item.physical_stock !== '' && item.physical_stock !== null ? parseInt(item.physical_stock) : null,
                notes: item.item_notes || null,
              })),
            };
          },

          async saveDraft() {
            if (!this.form.opname_date || !this.form.officer_name) {
              this.showFlash('Tanggal opname dan nama petugas wajib diisi.', false);
              return;
            }
            this.saving = true;
            try {
              const url = this.currentId ?
                `/admin/stock-opname/${this.currentId}` :
                '/admin/stock-opname';
              const method = this.currentId ? 'PUT' : 'POST';
              const res = await fetch(url, {
                method,
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.buildPayload()),
              });
              const data = await res.json();
              if (data.success) {
                if (!this.currentId && data.id) {
                  this.currentId = data.id;
                }
                this.showFlash(data.message, true);
              } else {
                this.showFlash(data.message || 'Terjadi kesalahan.', false);
              }
            } catch (e) {
              this.showFlash('Terjadi kesalahan jaringan.', false);
            } finally {
              this.saving = false;
            }
          },

          completeOpname() {
            if (!this.form.opname_date || !this.form.officer_name) {
              this.showFlash('Tanggal opname dan nama petugas wajib diisi.', false);
              return;
            }
            const counted = this.items.filter(i => i.physical_stock !== '' && i.physical_stock !== null).length;
            if (counted === 0) {
              this.showFlash('Belum ada stok fisik yang diisi.', false);
              return;
            }
            this.showConfirmModal = true;
          },

          async doComplete() {
            this.showConfirmModal = false;
            this.saving = true;
            try {
              const url = this.currentId ?
                `/admin/stock-opname/${this.currentId}/complete` :
                '/admin/stock-opname';

              // If no draft yet, save first then complete
              if (!this.currentId) {
                const storeRes = await fetch('/admin/stock-opname', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  },
                  body: JSON.stringify(this.buildPayload()),
                });
                const storeData = await storeRes.json();
                if (!storeData.success) {
                  this.showFlash(storeData.message || 'Gagal menyimpan.', false);
                  return;
                }
                this.currentId = storeData.id;
              }

              const res = await fetch(`/admin/stock-opname/${this.currentId}/complete`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.buildPayload()),
              });
              const data = await res.json();
              if (data.success) {
                this.showFlash(data.message, true);
                setTimeout(() => {
                  window.location.href = '{{ route('admin.stock-opname.history') }}';
                }, 1500);
              } else {
                this.showFlash(data.message || 'Terjadi kesalahan.', false);
              }
            } catch (e) {
              this.showFlash('Terjadi kesalahan jaringan.', false);
            } finally {
              this.saving = false;
            }
          },

          printForm() {
            window.print();
          },

          showFlash(message, success = true) {
            this.flashMessage = message;
            this.flashSuccess = success;
            if (success) {
              setTimeout(() => {
                this.flashMessage = '';
              }, 4000);
            }
          },
        };
      }
    </script>
  @endpush
</x-app-layout>
