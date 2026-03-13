<x-app-layout>
  <div class="p-6"
       x-data="menuForm()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center">
        <svg class="w-5 h-5 text-white"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Buat Menu</h1>
        <p class="text-sm text-gray-500">Kategori menu mengikuti pengaturan kategori yang dicentang sebagai menu di POS</p>
      </div>
    </div>

    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-800">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="font-semibold">Kategori menu aktif</p>
          <p class="mt-1 text-blue-700">Hanya kategori yang dicentang sebagai menu di pengaturan POS yang bisa dipakai di halaman ini.</p>
        </div>
        <a href="{{ route('admin.settings.pos-categories.index') }}"
           class="inline-flex items-center gap-2 self-start rounded-xl border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
          Atur Kategori POS
        </a>
      </div>

      @if ($menuCategoryTypes->isEmpty())
        <div class="mt-4 rounded-xl border border-dashed border-blue-300 bg-white px-4 py-3 text-blue-700">
          Belum ada kategori yang ditandai sebagai menu. Aktifkan dulu di pengaturan POS Categories.
        </div>
      @else
        <div class="mt-4 flex flex-wrap gap-2">
          @foreach ($menuCategoryTypes as $categoryType)
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
              {{ $categoryType }}
            </span>
          @endforeach
        </div>
      @endif
    </div>

    <!-- Notification -->
    <div x-show="notification.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         :class="notification.type === 'success' ? 'bg-green-50 border-green-400 text-green-800' : 'bg-red-50 border-red-400 text-red-800'"
         class="mb-5 px-4 py-3 border rounded-lg text-sm"
         style="display: none;">
      <span x-text="notification.message"></span>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">

      <!-- No & Name -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode Item <span class="text-red-500">*</span></label>
          <input type="text"
                 x-model="form.no"
                 placeholder="Contoh: MENU-001"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Menu <span class="text-red-500">*</span></label>
          <input type="text"
                 x-model="form.name"
                 placeholder="Contoh: Nasi Goreng Spesial"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>
      </div>

      <!-- Category, Unit, Price -->
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
          <select x-model="form.category_type"
                  @disabled($menuCategoryTypes->isEmpty())
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
            <option value="">{{ $menuCategoryTypes->isEmpty() ? 'Belum ada kategori menu aktif' : 'Pilih kategori' }}</option>
            @foreach ($menuCategoryTypes as $cat)
              <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Satuan <span class="text-red-500">*</span></label>
          <input type="text"
                 x-model="form.unit"
                 placeholder="Contoh: porsi"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Harga Jual (Rp) <span class="text-red-500">*</span></label>
          <input type="text"
                 :value="formatRupiahInput(form.selling_price)"
                 @input="onSellingPriceInput($event)"
                 placeholder="Rp 0"
                 inputmode="numeric"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent">
        </div>
      </div>

      <!-- Ingredients -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-sm font-medium text-gray-700">Bahan-bahan (Detail Group)</label>
          <button type="button"
                  @click="addIngredient()"
                  class="flex items-center gap-1 text-xs font-medium text-slate-700 border border-slate-300 rounded-lg px-2.5 py-1.5 hover:bg-slate-50 transition">
            <svg class="w-3.5 h-3.5"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            Tambah Bahan
          </button>
        </div>

        <!-- Table header -->
        <div x-show="form.detail_group.length > 0"
             class="grid grid-cols-12 gap-2 px-1 mb-1.5">
          <div class="col-span-7 text-xs font-medium text-gray-500">Bahan</div>
          <div class="col-span-4 text-xs font-medium text-gray-500 text-center">Jumlah</div>
          <div class="col-span-1"></div>
        </div>

        <div class="space-y-2">
          <template x-for="(row, index) in form.detail_group"
                    :key="index">
            <div class="grid grid-cols-12 gap-2 items-center">
              <div class="col-span-7">
                <select x-model="row.inventory_item_id"
                        @change="onRowItemChange(index)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent bg-white">
                  <option value="">-- Pilih Bahan --</option>
                  <template x-for="item in inventoryItems"
                            :key="item.id">
                    <option :value="item.id"
                            x-text="item.name + (item.unit ? ' (' + item.unit + ')' : '')"></option>
                  </template>
                </select>
              </div>
              <div class="col-span-4">
                <input type="number"
                       x-model="row.quantity"
                       placeholder="1"
                       min="1"
                       step="1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-500 focus:border-transparent text-center">
              </div>
              <div class="col-span-1 flex justify-center">
                <button type="button"
                        @click="removeRow(index)"
                        class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
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
            </div>
          </template>

          <div x-show="form.detail_group.length === 0"
               class="text-center py-5 text-sm text-gray-400 border border-dashed border-gray-300 rounded-lg">
            Belum ada bahan. Klik "Tambah Bahan" untuk menambahkan.
          </div>
        </div>
      </div>

      <!-- Error -->
      <p x-show="formError"
         x-text="formError"
         class="text-sm text-red-600"
         style="display: none;"></p>
    </div>

    <!-- Footer Actions -->
    <div class="mt-5 flex items-center justify-between">
      <button type="button"
              @click="resetForm()"
              class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
        Reset
      </button>
      <button type="button"
              @click="submitForm()"
              :disabled="saving || !hasMenuCategories"
              class="flex items-center gap-2 px-5 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
        <svg x-show="saving"
             class="w-4 h-4 animate-spin"
             fill="none"
             viewBox="0 0 24 24"
             style="display: none;">
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
        <span x-text="saving ? 'Menyimpan ke Accurate...' : 'Simpan ke Accurate'"></span>
      </button>
    </div>

    <div class="mt-8 space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-900">Daftar Menu</h2>
          <p class="text-sm text-gray-500">Menu ditampilkan berdasarkan kategori yang ditandai sebagai menu.</p>
        </div>
      </div>

      @if ($menuCategoryTypes->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500">
          Belum ada kategori menu aktif, jadi daftar menu belum dapat ditampilkan.
        </div>
      @else
        <div class="grid gap-5 xl:grid-cols-2">
          @foreach ($menuCategoryTypes as $categoryType)
            @php $menus = $menusByCategory->get($categoryType, collect()) @endphp
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
              <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                  <h3 class="text-base font-bold text-gray-900">{{ $categoryType }}</h3>
                  <p class="text-xs text-gray-500">{{ $menus->count() }} menu</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                  Menu Aktif
                </span>
              </div>

              @if ($menus->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-gray-500">
                  Belum ada menu pada kategori ini.
                </div>
              @else
                <div class="grid gap-3 p-4 sm:grid-cols-2">
                  @foreach ($menus as $menu)
                    <article class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm">
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <p class="truncate text-sm font-bold text-gray-900">{{ $menu->name }}</p>
                          <p class="mt-1 text-xs text-gray-500">{{ $menu->code }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                          {{ $menu->unit ?: '-' }}
                        </span>
                      </div>

                      <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs text-gray-500">Harga jual</span>
                        <span class="text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $menu->price, 0, ',', '.') }}</span>
                      </div>

                      <div class="mt-3 flex justify-end">
                        <button type="button"
                                onclick="openMenuDetailModal({{ $menu->id }}, '{{ addslashes($menu->name) }}')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                          <svg class="h-3.5 w-3.5"
                               fill="none"
                               stroke="currentColor"
                               viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          Detail Group
                        </button>
                      </div>
                    </article>
                  @endforeach
                </div>
              @endif
            </section>
          @endforeach
        </div>
      @endif
    </div>

    <div id="menuDetailGroupModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Detail Group</h2>
            <p id="menuDetailGroupItemName"
               class="text-sm text-gray-500"></p>
          </div>
          <button type="button"
                  onclick="closeMenuDetailModal()"
                  class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
            <svg class="h-5 w-5"
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

        <div class="px-5 py-4">
          <div id="menuDetailGroupLoading"
               class="py-8 text-center text-sm text-gray-500">Loading...</div>

          <div id="menuDetailGroupEmpty"
               class="hidden py-8 text-center text-sm text-gray-500">Tidak ada data detailGroup.</div>

          <div id="menuDetailGroupTableWrap"
               class="hidden overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500">
                  <th class="px-3 py-2">#</th>
                  <th class="px-3 py-2">Nama Detail</th>
                  <th class="px-3 py-2 text-right">Qty</th>
                  <th class="px-3 py-2">Unit</th>
                </tr>
              </thead>
              <tbody id="menuDetailGroupBody"
                     class="divide-y divide-gray-100"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  @push('scripts')
    <script>
      const inventoryItems = @json($inventoryItems);
      const hasMenuCategories = {{ $menuCategoryTypes->isNotEmpty() ? 'true' : 'false' }};
      const menuDetailRouteTemplate = "{{ route('admin.menus.fetch-detail', ['inventory' => '__INVENTORY__']) }}";

      function openMenuDetailModal(itemId, itemName) {
        const modal = document.getElementById('menuDetailGroupModal');
        const itemNameEl = document.getElementById('menuDetailGroupItemName');
        const loading = document.getElementById('menuDetailGroupLoading');
        const empty = document.getElementById('menuDetailGroupEmpty');
        const tableWrap = document.getElementById('menuDetailGroupTableWrap');
        const body = document.getElementById('menuDetailGroupBody');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        itemNameEl.textContent = itemName;
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        tableWrap.classList.add('hidden');
        body.innerHTML = '';

        const detailUrl = menuDetailRouteTemplate.replace('__INVENTORY__', String(itemId));

        fetch(detailUrl, {
            headers: {
              Accept: 'application/json',
            },
          })
          .then((response) => response.json())
          .then((data) => {
            loading.classList.add('hidden');

            if (!data.success || !Array.isArray(data.detail_group) || data.detail_group.length === 0) {
              empty.textContent = data.message || 'Tidak ada data detailGroup.';
              empty.classList.remove('hidden');

              return;
            }

            body.innerHTML = data.detail_group.map((group, index) => `
              <tr>
                <td class="px-3 py-2 text-gray-500">${index + 1}</td>
                <td class="px-3 py-2 font-medium text-gray-800">${group.detail_name ?? '-'}</td>
                <td class="px-3 py-2 text-right text-gray-700">${group.quantity ?? 0}</td>
                <td class="px-3 py-2 text-gray-500">${group.unit ?? '-'}</td>
              </tr>
            `).join('');

            tableWrap.classList.remove('hidden');
          })
          .catch(() => {
            loading.classList.add('hidden');
            empty.textContent = 'Gagal mengambil data.';
            empty.classList.remove('hidden');
          });
      }

      function closeMenuDetailModal() {
        const modal = document.getElementById('menuDetailGroupModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }

      document.addEventListener('click', function(e) {
        const modal = document.getElementById('menuDetailGroupModal');
        if (e.target === modal) {
          closeMenuDetailModal();
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeMenuDetailModal();
        }
      });

      function menuForm() {
        return {
          inventoryItems,
          hasMenuCategories,
          saving: false,
          formError: '',
          notification: {
            show: false,
            type: 'success',
            message: ''
          },
          form: {
            no: '',
            name: '',
            category_type: '',
            unit: '',
            selling_price: '',
            detail_group: [],
          },

          showNotification(type, message) {
            this.notification = {
              show: true,
              type,
              message
            };
            setTimeout(() => {
              this.notification.show = false;
            }, 5000);
          },

          addIngredient() {
            this.form.detail_group.push({
              inventory_item_id: '',
              item_no: '',
              detail_name: '',
              quantity: 1
            });
          },

          onRowItemChange(index) {
            const row = this.form.detail_group[index];
            const item = this.inventoryItems.find(i => i.id == row.inventory_item_id);
            if (item) {
              row.item_no = item.code;
              row.detail_name = item.name;
            } else {
              row.item_no = '';
              row.detail_name = '';
            }
          },

          removeRow(index) {
            this.form.detail_group.splice(index, 1);
          },

          formatRupiahInput(value) {
            const numeric = String(value ?? '').replace(/\D/g, '');

            if (!numeric) {
              return '';
            }

            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(numeric));
          },

          onSellingPriceInput(event) {
            const numeric = event.target.value.replace(/\D/g, '');
            this.form.selling_price = numeric;
            event.target.value = this.formatRupiahInput(numeric);
          },

          resetForm() {
            this.form = {
              no: '',
              name: '',
              category_type: '',
              unit: '',
              selling_price: '',
              detail_group: [],
            };
            this.formError = '';
          },

          async submitForm() {
            if (!this.hasMenuCategories) {
              this.formError = 'Belum ada kategori yang ditandai sebagai menu di pengaturan POS.';
              return;
            }
            if (!this.form.no.trim()) {
              this.formError = 'Kode item wajib diisi.';
              return;
            }
            if (!this.form.name.trim()) {
              this.formError = 'Nama menu wajib diisi.';
              return;
            }
            if (!this.form.category_type.trim()) {
              this.formError = 'Kategori menu wajib dipilih.';
              return;
            }
            if (!this.form.unit.trim()) {
              this.formError = 'Satuan wajib diisi.';
              return;
            }
            if (this.form.selling_price === '' || this.form.selling_price === null) {
              this.formError = 'Harga jual wajib diisi.';
              return;
            }

            const detailGroup = this.form.detail_group
              .filter((row) => row.item_no.trim())
              .map((row) => ({
                ...row,
                quantity: Number(row.quantity),
              }));

            const invalidQuantity = detailGroup.find((row) => !Number.isInteger(row.quantity) || row.quantity <= 0);
            if (invalidQuantity) {
              this.formError = 'Jumlah bahan wajib bilangan bulat dan lebih dari 0.';
              return;
            }

            this.formError = '';
            this.saving = true;

            try {
              const response = await fetch('{{ route('admin.menus.store') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  'Accept': 'application/json',
                },
                body: JSON.stringify({
                  no: this.form.no,
                  name: this.form.name,
                  category_type: this.form.category_type,
                  unit: this.form.unit,
                  selling_price: Number(this.form.selling_price || 0),
                  detail_group: detailGroup,
                }),
              });

              const data = await response.json();

              if (data.success) {
                this.showNotification('success', 'Menu "' + this.form.name + '" berhasil disimpan ke Accurate.');
                this.resetForm();
              } else {
                if (data.errors) {
                  this.formError = Object.values(data.errors).flat().join(' ');
                } else {
                  this.formError = data.message || 'Gagal menyimpan ke Accurate.';
                }
              }
            } catch (e) {
              this.formError = 'Terjadi kesalahan jaringan.';
            } finally {
              this.saving = false;
            }
          },
        };
      }
    </script>
  @endpush
</x-app-layout>
