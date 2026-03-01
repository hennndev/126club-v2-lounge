<x-app-layout>
  <div class="p-6"
       x-data="rewardManager()">
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
    <div class="flex items-start justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Reward Management</h1>
          <p class="text-sm text-gray-500">Kelola hadiah dan reward untuk program loyalitas</p>
        </div>
      </div>
      <button @click="openAddModal()"
              class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-lg font-medium transition">
        <svg class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4" />
        </svg>
        Tambah Reward
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <!-- Total Rewards -->
      <div class="bg-purple-100 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-purple-600 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-purple-700 font-medium">Total Rewards</p>
          <p class="text-3xl font-bold text-purple-800">{{ $totalRewards }}</p>
        </div>
      </div>

      <!-- Total Stock -->
      <div class="bg-blue-100 rounded-xl p-5 flex items-center gap-4">
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
          <p class="text-sm text-blue-700 font-medium">Total Stock</p>
          <p class="text-3xl font-bold text-blue-800">{{ number_format($totalStock, 0, ',', '.') }}</p>
        </div>
      </div>

      <!-- Total Points Value -->
      <div class="bg-orange-100 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-orange-500 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="currentColor"
               viewBox="0 0 24 24">
            <path d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-orange-700 font-medium">Total Points Value</p>
          <p class="text-3xl font-bold text-orange-800">{{ number_format($totalPointsValue, 0, ',', '.') }}</p>
        </div>
      </div>

      <!-- Redeemed -->
      <div class="bg-green-100 rounded-xl p-5 flex items-center gap-4">
        <div class="bg-green-600 rounded-xl p-3 flex-shrink-0">
          <svg class="w-7 h-7 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
        </div>
        <div>
          <p class="text-sm text-green-700 font-medium">Redeemed</p>
          <p class="text-3xl font-bold text-green-800">{{ $totalRedeemed }}</p>
        </div>
      </div>
    </div>

    <!-- Available Rewards -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <!-- Section Header -->
      <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
          </svg>
        </div>
        <div>
          <h2 class="font-semibold text-gray-900 text-lg">Available Rewards</h2>
          <p class="text-sm text-gray-500">Daftar semua reward yang tersedia</p>
        </div>
      </div>

      <!-- Rewards Grid -->
      @if ($rewards->isEmpty())
        <div class="p-12 text-center text-gray-400">
          <svg class="w-12 h-12 mx-auto mb-3 text-gray-300"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
          </svg>
          <p class="font-medium">Belum ada reward</p>
          <p class="text-sm">Tambah reward pertama untuk program loyalitas</p>
        </div>
      @else
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
          @foreach ($rewards as $reward)
            @php
              $iconBg = match ($reward->category) {
                  'drink' => 'bg-purple-600',
                  'voucher' => 'bg-blue-500',
                  'vip' => 'bg-yellow-500',
                  default => 'bg-gray-500',
              };
              $badgeBg = match ($reward->category) {
                  'drink' => 'bg-purple-100 text-purple-700',
                  'voucher' => 'bg-blue-100 text-blue-700',
                  'vip' => 'bg-yellow-100 text-yellow-700',
                  default => 'bg-gray-100 text-gray-700',
              };
              $pointsColor = match ($reward->category) {
                  'drink' => 'text-purple-600',
                  'voucher' => 'text-blue-600',
                  'vip' => 'text-yellow-600',
                  default => 'text-gray-600',
              };
              $pointsBg = match ($reward->category) {
                  'drink' => 'bg-purple-50',
                  'voucher' => 'bg-blue-50',
                  'vip' => 'bg-yellow-50',
                  default => 'bg-gray-50',
              };
            @endphp
            <div class="border border-gray-200 rounded-xl overflow-hidden">
              <!-- Card Header -->
              <div class="p-4 flex items-start gap-3">
                <div class="{{ $iconBg }} rounded-xl p-3 flex-shrink-0">
                  @if ($reward->category === 'drink')
                    <svg class="w-6 h-6 text-white"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                  @elseif ($reward->category === 'voucher')
                    <svg class="w-6 h-6 text-white"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  @else
                    <svg class="w-6 h-6 text-white"
                         fill="currentColor"
                         viewBox="0 0 24 24">
                      <path d="M5 3l3.057-3 11.943 12-11.943 12-3.057-3 9-9z" />
                    </svg>
                  @endif
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ $reward->name }}</h3>
                  <span class="inline-block mt-1 px-2 py-0.5 text-xs font-semibold rounded {{ $badgeBg }}">
                    {{ strtoupper($reward->category) }}
                  </span>
                </div>
              </div>

              <!-- Description -->
              <div class="px-4 pb-3">
                <p class="text-sm text-gray-500">{{ $reward->description }}</p>
              </div>

              <!-- Points -->
              <div class="{{ $pointsBg }} mx-4 mb-3 rounded-lg px-4 py-2.5 flex items-center gap-2">
                <svg class="w-5 h-5 {{ $pointsColor }}"
                     fill="currentColor"
                     viewBox="0 0 24 24">
                  <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="{{ $pointsColor }} font-bold text-lg">{{ number_format($reward->points_required, 0, ',', '.') }}</span>
                <span class="{{ $pointsColor }} font-medium text-sm">points</span>
              </div>

              <!-- Stock -->
              <div class="mx-4 mb-4 flex items-center justify-between border border-gray-200 rounded-lg px-4 py-2.5">
                <span class="text-sm font-medium text-gray-600">Stock Available:</span>
                <span class="text-sm font-semibold text-gray-800">{{ $reward->stock }} pcs</span>
              </div>

              <!-- Actions -->
              <div class="border-t border-gray-100 grid grid-cols-2 divide-x divide-gray-100">
                <button @click="openEditModal({{ json_encode(['id' => $reward->id, 'name' => $reward->name, 'category' => $reward->category, 'description' => $reward->description, 'points_required' => $reward->points_required, 'stock' => $reward->stock]) }})"
                        class="flex items-center justify-center gap-2 py-3 text-sm text-gray-600 hover:bg-gray-50 transition">
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Edit
                </button>
                <button @click="confirmDelete({{ $reward->id }}, '{{ addslashes($reward->name) }}')"
                        class="flex items-center justify-center gap-2 py-3 text-sm text-red-500 hover:bg-red-50 transition">
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          @endforeach
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
              :method="'POST'"
              id="rewardForm">
          @csrf
          <div x-show="isEdit">
            <input type="hidden"
                   name="_method"
                   value="PUT">
          </div>
          <div class="px-6 py-5 space-y-4">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Reward <span class="text-red-500">*</span></label>
              <input type="text"
                     name="name"
                     :value="form.name"
                     @input="form.name = $event.target.value"
                     placeholder="Contoh: Free House Cocktail"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                     required>
            </div>

            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
              <select name="category"
                      x-model="form.category"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                      required>
                <option value="">Pilih kategori</option>
                <option value="drink">Drink</option>
                <option value="voucher">Voucher</option>
                <option value="vip">VIP</option>
              </select>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
              <textarea name="description"
                        :value="form.description"
                        @input="form.description = $event.target.value"
                        placeholder="Deskripsi singkat tentang reward ini..."
                        rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm resize-none"></textarea>
            </div>

            <!-- Points & Stock -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Points Dibutuhkan <span class="text-red-500">*</span></label>
                <input type="number"
                       name="points_required"
                       :value="form.points_required"
                       @input="form.points_required = $event.target.value"
                       min="1"
                       placeholder="500"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                       required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Stok <span class="text-red-500">*</span></label>
                <input type="number"
                       name="stock"
                       :value="form.stock"
                       @input="form.stock = $event.target.value"
                       min="0"
                       placeholder="50"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                       required>
              </div>
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
                    class="px-4 py-2.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition">
              <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Reward'"></span>
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
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Hapus Reward</h3>
        <p class="text-sm text-gray-500 mb-6">Yakin ingin menghapus <strong x-text="deleteRewardName"></strong>? Tindakan ini tidak bisa dibatalkan.</p>
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
      function rewardManager() {
        return {
          showModal: false,
          showDeleteModal: false,
          isEdit: false,
          modalTitle: 'Tambah Reward',
          formAction: '{{ route('admin.rewards.store') }}',
          deleteAction: '',
          deleteRewardName: '',
          form: {
            name: '',
            category: '',
            description: '',
            points_required: '',
            stock: '',
          },

          openAddModal() {
            this.isEdit = false;
            this.modalTitle = 'Tambah Reward';
            this.formAction = '{{ route('admin.rewards.store') }}';
            this.form = {
              name: '',
              category: '',
              description: '',
              points_required: '',
              stock: ''
            };
            this.showModal = true;
          },

          openEditModal(reward) {
            this.isEdit = true;
            this.modalTitle = 'Edit Reward';
            this.formAction = `/admin/rewards/${reward.id}`;
            this.form = {
              name: reward.name,
              category: reward.category,
              description: reward.description || '',
              points_required: reward.points_required,
              stock: reward.stock,
            };
            this.showModal = true;
          },

          confirmDelete(id, name) {
            this.deleteRewardName = name;
            this.deleteAction = `/admin/rewards/${id}`;
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
