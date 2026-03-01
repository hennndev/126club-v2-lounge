<x-app-layout>
  <div class="p-6"
       x-data="keepManager()">
    @if (session('success'))
      <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div>
        <div class="flex items-center gap-3 mb-1">
          <h1 class="text-2xl font-bold text-gray-900">Customer Keep</h1>
          <span class="px-3 py-1 text-xs font-semibold rounded-full
            {{ $todayType === 'weekday' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
            📅 Hari ini: {{ $todayLabel }}
          </span>
        </div>
        <p class="text-sm text-gray-500">Kelola produk pelanggan yang disimpan untuk kunjungan selanjutnya</p>
      </div>
      <button @click="openAddModal()"
              class="flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg font-medium transition whitespace-nowrap">
        <svg class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4" />
        </svg>
        Tambah Item Keep
      </button>
    </div>

    <!-- Warning Info Box -->
    <div class="mb-6 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg flex items-start gap-2 text-sm">
      <span class="text-base">⚑</span>
      <p>
        <strong>Perhatian:</strong> Minuman
        <span class="font-semibold text-blue-600">Weekday</span>
        hanya bisa dibuka Senin-Kamis. Minuman
        <span class="font-semibold text-purple-600">Weekend/Event</span>
        bisa dibuka kapan saja.
      </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <!-- Active Items -->
      <div class="bg-green-50 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-green-500 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-green-700 font-medium">Active Items</p>
          <p class="text-3xl font-bold text-green-800">{{ $totalActive }}</p>
        </div>
      </div>

      <!-- Used Items -->
      <div class="bg-gray-100 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-slate-600 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-gray-600 font-medium">Used Items</p>
          <p class="text-3xl font-bold text-gray-800">{{ $totalUsed }}</p>
        </div>
      </div>

      <!-- Total Items -->
      <div class="bg-blue-50 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-blue-600 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-blue-700 font-medium">Total Items</p>
          <p class="text-3xl font-bold text-blue-800">{{ $totalItems }}</p>
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <!-- Tabs -->
      <div class="px-6 pt-5 pb-0 border-b border-gray-100">
        <div class="flex gap-1">
          <a href="{{ route('admin.customer-keep.index') }}"
             class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition
               {{ $tab === 'all' ? 'border-slate-800 text-slate-800 bg-gray-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Semua ({{ $totalItems }})
          </a>
          <a href="{{ route('admin.customer-keep.index', ['tab' => 'active']) }}"
             class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition
               {{ $tab === 'active' ? 'border-slate-800 text-slate-800 bg-gray-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Active ({{ $totalActive }})
          </a>
          <a href="{{ route('admin.customer-keep.index', ['tab' => 'used']) }}"
             class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition
               {{ $tab === 'used' ? 'border-slate-800 text-slate-800 bg-gray-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Used ({{ $totalUsed }})
          </a>
        </div>
      </div>

      <!-- Table / Empty State -->
      @if ($keeps->isEmpty())
        <div class="p-16 text-center text-gray-400">
          <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <p class="font-semibold text-gray-600">Tidak ada item keep</p>
          <p class="text-sm mt-1">Tambahkan item keep untuk memulai</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-100 text-left">
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Simpan</th>
                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              @foreach ($keeps as $keep)
                <tr class="hover:bg-gray-50 transition">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 bg-slate-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-semibold text-slate-600">
                          {{ strtoupper(substr($keep->customerUser->user->name ?? '?', 0, 1)) }}
                        </span>
                      </div>
                      <div>
                        <p class="font-medium text-gray-900 text-sm">{{ $keep->customerUser->user->name ?? '-' }}</p>
                        <p class="text-xs text-gray-400">{{ $keep->customerUser->customer_code ?? '' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <p class="font-medium text-gray-900 text-sm">{{ $keep->item_name }}</p>
                    @if ($keep->notes)
                      <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($keep->notes, 40) }}</p>
                    @endif
                  </td>
                  <td class="px-6 py-4">
                    @if ($keep->type === 'weekday')
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Weekday</span>
                    @else
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">Weekend/Event</span>
                    @endif
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-700">
                    {{ rtrim(rtrim(number_format($keep->quantity, 2), '0'), '.') }} {{ $keep->unit }}
                  </td>
                  <td class="px-6 py-4">
                    @if ($keep->status === 'active')
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Active</span>
                    @else
                      <div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Used</span>
                        @if ($keep->opened_at)
                          <p class="text-xs text-gray-400 mt-1">{{ $keep->opened_at->format('d M Y') }}</p>
                        @endif
                      </div>
                    @endif
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $keep->stored_at ? $keep->stored_at->format('d M Y') : $keep->created_at->format('d M Y') }}
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      @if ($keep->status === 'active')
                        <form action="{{ route('admin.customer-keep.mark-used', $keep) }}"
                              method="POST">
                          @csrf
                          @method('PATCH')
                          <button type="submit"
                                  class="px-2.5 py-1.5 text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200 rounded-lg transition"
                                  onclick="return confirm('Tandai item ini sebagai sudah digunakan?')">
                            Tandai Used
                          </button>
                        </form>
                      @endif
                      <button @click="openEditModal({{ json_encode(['id' => $keep->id, 'customer_user_id' => $keep->customer_user_id, 'item_name' => $keep->item_name, 'type' => $keep->type, 'quantity' => $keep->quantity, 'unit' => $keep->unit, 'notes' => $keep->notes ?? '']) }})"
                              class="px-2.5 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition">
                        Edit
                      </button>
                      <button @click="confirmDelete({{ $keep->id }}, '{{ addslashes($keep->item_name) }}')"
                              class="px-2.5 py-1.5 text-xs font-medium bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition">
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    <!-- Add / Edit Modal -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         style="display: none;">
      <div @click.stop
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900"
              x-text="modalTitle"></h2>
          <button @click="closeModal()"
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

        <!-- Modal Body -->
        <form :action="formAction"
              method="POST"
              id="keepForm">
          @csrf
          <div x-show="isEdit">
            <input type="hidden"
                   name="_method"
                   value="PUT">
          </div>
          <div class="px-6 py-5 space-y-4">
            <!-- Customer -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Customer
                <span class="text-red-500">*</span>
              </label>
              <select name="customer_user_id"
                      x-model="form.customer_user_id"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm"
                      required>
                <option value="">Pilih customer</option>
                @foreach ($customers as $customer)
                  <option value="{{ $customer->id }}">
                    {{ $customer->user->name ?? 'N/A' }} ({{ $customer->customer_code }})
                  </option>
                @endforeach
              </select>
            </div>

            <!-- Item Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Item
                <span class="text-red-500">*</span>
              </label>
              <input type="text"
                     name="item_name"
                     :value="form.item_name"
                     @input="form.item_name = $event.target.value"
                     placeholder="Contoh: Johnnie Walker Black Label"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm"
                     required>
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipe Keep
                <span class="text-red-500">*</span>
              </label>
              <select name="type"
                      x-model="form.type"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm"
                      required>
                <option value="">Pilih tipe</option>
                <option value="weekday">Weekday (Senin–Kamis)</option>
                <option value="weekend_event">Weekend/Event (kapan saja)</option>
              </select>
            </div>

            <!-- Quantity & Unit -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Jumlah
                  <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       name="quantity"
                       :value="form.quantity"
                       @input="form.quantity = $event.target.value"
                       step="0.5"
                       min="0.5"
                       placeholder="1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm"
                       required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Satuan
                  <span class="text-red-500">*</span>
                </label>
                <select name="unit"
                        x-model="form.unit"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm"
                        required>
                  <option value="bottle">Bottle</option>
                  <option value="glass">Glass</option>
                  <option value="pcs">Pcs</option>
                  <option value="ml">mL</option>
                </select>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Catatan</label>
              <textarea name="notes"
                        :value="form.notes"
                        @input="form.notes = $event.target.value"
                        placeholder="Catatan tambahan (opsional)..."
                        rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-slate-500 focus:border-transparent text-sm resize-none"></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button"
                    @click="closeModal()"
                    class="px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              Batal
            </button>
            <button type="submit"
                    class="px-4 py-2.5 text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition">
              <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Item Keep'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div x-show="showDeleteModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         style="display: none;">
      <div @click.stop
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-red-500"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Hapus Item Keep</h3>
        <p class="text-sm text-gray-500 mb-6">Yakin ingin menghapus
          <strong x-text="deleteItemName"></strong>? Tindakan ini tidak bisa dibatalkan.
        </p>
        <div class="flex gap-3">
          <button @click="showDeleteModal = false"
                  class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Batal
          </button>
          <form :action="deleteAction"
                method="POST"
                class="flex-1">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition">
              Hapus
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      function keepManager() {
        return {
          showModal: false,
          showDeleteModal: false,
          isEdit: false,
          modalTitle: 'Tambah Item Keep',
          formAction: '{{ route('admin.customer-keep.store') }}',
          deleteAction: '',
          deleteItemName: '',
          form: {
            customer_user_id: '',
            item_name: '',
            type: '',
            quantity: 1,
            unit: 'bottle',
            notes: '',
          },

          openAddModal() {
            this.isEdit = false;
            this.modalTitle = 'Tambah Item Keep';
            this.formAction = '{{ route('admin.customer-keep.store') }}';
            this.form = {
              customer_user_id: '',
              item_name: '',
              type: '',
              quantity: 1,
              unit: 'bottle',
              notes: ''
            };
            this.showModal = true;
          },

          openEditModal(keep) {
            this.isEdit = true;
            this.modalTitle = 'Edit Item Keep';
            this.formAction = `/admin/customer-keep/${keep.id}`;
            this.form = {
              customer_user_id: String(keep.customer_user_id),
              item_name: keep.item_name,
              type: keep.type,
              quantity: keep.quantity,
              unit: keep.unit,
              notes: keep.notes || '',
            };
            this.showModal = true;
          },

          confirmDelete(id, name) {
            this.deleteItemName = name;
            this.deleteAction = `/admin/customer-keep/${id}`;
            this.showDeleteModal = true;
          },

          closeModal() {
            this.showModal = false;
          },
        };
      }
    </script>
  @endpush
</x-app-layout>
