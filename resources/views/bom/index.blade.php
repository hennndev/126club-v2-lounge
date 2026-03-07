<x-app-layout>
  <div class="p-6">
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
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">BOM - Bill of Materials</h1>
        <p class="text-sm text-gray-500">Kelola resep makanan dan minuman</p>
      </div>
      <div class="flex items-center gap-3">
        <button data-bom-sync-btn
                onclick="syncBomFromAccurate()"
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
          <span data-bom-sync-icon
                class="flex items-center">
            <svg class="w-5 h-5"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </span>
          <span data-bom-sync-text>Sync dari Accurate</span>
        </button>
        <button onclick="openModal('add')"
                class="px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition flex items-center gap-2">
          <svg class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4" />
          </svg>
          Tambah Recipe
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Total Recipes</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalRecipes }}</p>
          </div>
          <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-teal-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Food Recipes</p>
            <p class="text-2xl font-bold text-gray-900">{{ $foodRecipes }}</p>
          </div>
          <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
        </div>
      </div>
      <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">Beverage Recipes</p>
            <p class="text-2xl font-bold text-gray-900">{{ $beverageRecipes }}</p>
          </div>
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-2 mb-6 bg-white border border-gray-200 rounded-xl p-1.5">
      <button onclick="filterByType('')"
              id="tabAll"
              class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition bg-slate-800 text-white">
        All ({{ $totalRecipes }})
      </button>
      <button onclick="filterByType('food')"
              id="tabFood"
              class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition text-gray-600 hover:bg-gray-100">
        Food ({{ $foodRecipes }})
      </button>
      <button onclick="filterByType('beverage')"
              id="tabBeverage"
              class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition text-gray-600 hover:bg-gray-100">
        Beverage ({{ $beverageRecipes }})
      </button>
    </div>

    @if ($recipes->count() > 0)
      <!-- Search -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="p-4">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text"
                   id="searchInput"
                   placeholder="Cari recipe berdasarkan nama..."
                   class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
          </div>
        </div>
      </div>

      <!-- Recipe Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
           id="recipeGrid">
        @foreach ($recipes as $recipe)
          <div class="recipe-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition"
               data-type="{{ $recipe->type }}"
               data-name="{{ strtolower($recipe->name) }}">
            <div class="p-6">
              <!-- Header -->
              <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <h3 class="text-base font-bold text-gray-900">{{ $recipe->name }}</h3>
                    @if ($recipe->type === 'food')
                      <span class="px-2 py-0.5 text-xs font-medium rounded bg-orange-100 text-orange-700">Food</span>
                    @else
                      <span class="px-2 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-700">Beverage</span>
                    @endif
                    @if ($recipe->is_available)
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">Tersedia</span>
                    @else
                      <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">Habis</span>
                    @endif
                  </div>
                  @if ($recipe->description)
                    <p class="text-sm text-gray-500">{{ $recipe->description }}</p>
                  @endif
                </div>
              </div>

              <!-- Ingredients -->
              <div class="mb-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Bahan:</h4>
                <div class="space-y-1">
                  @foreach ($recipe->items as $item)
                    <div class="flex items-center justify-between text-sm">
                      <span class="text-gray-700">{{ $item->inventoryItem->name }}</span>
                      <span class="text-gray-500 font-medium">{{ $item->quantity }} {{ $item->unit }}</span>
                    </div>
                  @endforeach
                </div>
              </div>

              <!-- Pricing -->
              <div class="border-t border-gray-200 pt-4 space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Total Cost:</span>
                  <span class="font-semibold text-gray-900">Rp {{ number_format($recipe->total_cost, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Selling Price:</span>
                  <span class="font-semibold text-teal-600">Rp {{ number_format($recipe->selling_price, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Profit Margin:</span>
                  <span class="font-semibold {{ $recipe->profit_margin > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($recipe->profit_margin, 1) }}%
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                <form action="{{ route('admin.bom.toggleAvailability', $recipe) }}"
                      method="POST"
                      class="flex-1">
                  @csrf @method('PATCH')
                  <button type="submit"
                          class="w-full px-3 py-2 text-xs font-semibold rounded-lg transition
                            {{ $recipe->is_available ? 'bg-red-50 text-red-600 border border-red-200 hover:bg-red-100' : 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' }}">
                    {{ $recipe->is_available ? 'Tandai Habis' : 'Tandai Tersedia' }}
                  </button>
                </form>
                <button onclick="editRecipe({{ $recipe->id }})"
                        class="p-2 text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition"
                        title="Edit">
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button onclick="deleteRecipe({{ $recipe->id }})"
                        class="p-2 text-gray-500 border border-gray-200 rounded-lg hover:bg-red-50 hover:text-red-600 transition"
                        title="Hapus">
                  <svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <!-- Empty State -->
      <div class="bg-gradient-to-br from-teal-50 to-white border-2 border-dashed border-teal-300 rounded-xl p-12 text-center">
        <div class="flex items-center justify-center w-20 h-20 mx-auto bg-teal-100 rounded-full mb-4">
          <svg class="w-10 h-10 text-teal-500"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Belum Ada Recipe</h3>
        <p class="text-gray-500 mb-6">Mulai tambahkan recipe untuk makanan dan minuman</p>
        <button onclick="openModal('add')"
                class="px-6 py-3 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition inline-flex items-center gap-2">
          <svg class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4" />
          </svg>
          Tambah Recipe Pertama
        </button>
      </div>
    @endif
  </div>

  <!-- Add/Edit Modal -->
  @include('bom._components.add-edit-modal')

  <!-- Delete Modal -->
  @include('bom._components.delete-confirmation-modal')

  <!-- Sync Result Modal -->
  <div id="bomSyncResultModal"
       class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
      <div class="p-6">
        <div id="bomSyncResultIcon"
             class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4">
        </div>
        <h3 id="bomSyncResultTitle"
            class="text-lg font-bold text-gray-900 text-center mb-1"></h3>
        <p id="bomSyncResultMessage"
           class="text-sm text-gray-500 text-center mb-4"></p>
        <pre id="bomSyncResultOutput"
             class="hidden bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-600 max-h-40 overflow-y-auto whitespace-pre-wrap mb-4"></pre>
        <button onclick="document.getElementById('bomSyncResultModal').classList.add('hidden'); window.location.reload();"
                class="w-full px-4 py-2.5 bg-slate-800 text-white rounded-xl hover:bg-slate-900 font-semibold transition">
          Tutup &amp; Refresh
        </button>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      const recipes = @json($recipes);
      const inventoryItems = @json($inventoryItems);
      let ingredientCounter = 0;

      const BOM_SYNC_ICON_HTML = document.querySelector('[data-bom-sync-icon]').innerHTML;

      function syncBomFromAccurate() {
        const btn = document.querySelector('[data-bom-sync-btn]');
        const icon = document.querySelector('[data-bom-sync-icon]');
        const text = document.querySelector('[data-bom-sync-text]');

        btn.disabled = true;
        icon.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>`;
        text.textContent = 'Syncing...';

        fetch('{{ route('admin.accurate.sync.bom') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          })
          .then(res => res.json())
          .then(data => {
            btn.disabled = false;
            icon.innerHTML = BOM_SYNC_ICON_HTML;
            text.textContent = 'Sync dari Accurate';
            showBomSyncResult(data.success, data.message, data.output ?? null);
          })
          .catch(err => {
            btn.disabled = false;
            icon.innerHTML = BOM_SYNC_ICON_HTML;
            text.textContent = 'Sync dari Accurate';
            showBomSyncResult(false, 'Koneksi gagal: ' + err.message, null);
          });
      }

      function showBomSyncResult(success, message, output) {
        const modal = document.getElementById('bomSyncResultModal');
        const icon = document.getElementById('bomSyncResultIcon');
        const title = document.getElementById('bomSyncResultTitle');
        const msg = document.getElementById('bomSyncResultMessage');
        const pre = document.getElementById('bomSyncResultOutput');

        if (success) {
          icon.className = 'w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-100';
          icon.innerHTML = `<svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
          title.textContent = 'Sync Berhasil!';
        } else {
          icon.className = 'w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100';
          icon.innerHTML = `<svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
          title.textContent = 'Sync Gagal';
        }

        msg.textContent = message;

        if (output && output.trim()) {
          pre.textContent = output.trim();
          pre.classList.remove('hidden');
        } else {
          pre.classList.add('hidden');
        }

        modal.classList.remove('hidden');
      }

      function openModal(mode, recipeId = null) {
        const modal = document.getElementById('recipeModal');
        const form = document.getElementById('recipeForm');
        const modalTitle = document.getElementById('modalTitle');
        const formMethod = document.getElementById('formMethod');
        const ingredientsList = document.getElementById('ingredientsList');

        ingredientsList.innerHTML = '';
        ingredientCounter = 0;

        if (mode === 'add') {
          modalTitle.textContent = 'Tambah Recipe';
          form.action = '{{ route('admin.bom.store') }}';
          formMethod.value = 'POST';
          form.reset();
          addIngredient();
        } else if (mode === 'edit' && recipeId) {
          const recipe = recipes.find(r => r.id === recipeId);
          if (recipe) {
            modalTitle.textContent = 'Edit Recipe';
            form.action = `/admin/bom/${recipeId}`;
            formMethod.value = 'PUT';

            document.getElementById('inventory_item_id').value = recipe.inventory_item_id;
            document.getElementById('quantity').value = recipe.quantity;
            document.getElementById('description').value = recipe.description || '';
            document.getElementById('selling_price').value = recipe.selling_price;
            document.querySelector(`input[name="type"][value="${recipe.type}"]`).checked = true;

            recipe.items.forEach(item => {
              addIngredient(item.inventory_item_id, item.quantity);
            });
          }
        }

        modal.classList.remove('hidden');
      }

      function closeModal() {
        document.getElementById('recipeModal').classList.add('hidden');
      }

      function addIngredient(itemId = '', quantity = '') {
        const container = document.getElementById('ingredientsList');
        const index = ingredientCounter++;

        // Filter hanya condiments (case insensitive)
        const condimentsItems = inventoryItems.filter(item =>
          item.category_type && item.category_type.toLowerCase() === 'condiments'
        );

        console.log('All inventory items:', inventoryItems);
        console.log('Condiments items:', condimentsItems);
        console.log('Categories found:', [...new Set(inventoryItems.map(i => i.category_type))]);

        const div = document.createElement('div');
        div.className = 'flex gap-2';

        if (condimentsItems.length === 0) {
          div.innerHTML = `
            <div class="flex-1 px-3 py-2 border border-red-300 rounded-lg bg-red-50 text-red-600 text-sm">
              ⚠️ Tidak ada item Condiments. Silakan tambah item dengan category_type = "condiments" terlebih dahulu.
            </div>
            <button type="button" onclick="this.parentElement.remove()"
                    class="px-3 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          `;
        } else {
          div.innerHTML = `
            <select name="items[${index}][inventory_item_id]" required
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm">
              <option value="">Pilih Bahan (Condiments)</option>
              ${condimentsItems.map(item => `
                                                    <option value="${item.id}" ${item.id == itemId ? 'selected' : ''}>
                                                      ${item.name} (${item.unit})
                                                    </option>
                                                  `).join('')}
            </select>
            <input type="number" name="items[${index}][quantity]" value="${quantity}" required min="0.01" step="0.01"
                   placeholder="Qty"
                   class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm">
            <button type="button" onclick="this.parentElement.remove()"
                    class="px-3 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          `;
        }

        container.appendChild(div);
      }

      function editRecipe(recipeId) {
        openModal('edit', recipeId);
      }

      function deleteRecipe(recipeId) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/bom/${recipeId}`;
        document.getElementById('deleteModal').classList.remove('hidden');
      }

      function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
      }

      // Filter by type
      function filterByType(type) {
        const cards = document.querySelectorAll('.recipe-card');

        // Reset all tabs
        document.getElementById('tabAll').className = 'flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition text-gray-600 hover:bg-gray-100';
        document.getElementById('tabFood').className = 'flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition text-gray-600 hover:bg-gray-100';
        document.getElementById('tabBeverage').className = 'flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition text-gray-600 hover:bg-gray-100';

        // Set active tab
        const activeClass = 'flex-1 px-4 py-2 rounded-lg text-sm font-semibold transition bg-slate-800 text-white';
        if (type === '') document.getElementById('tabAll').className = activeClass;
        if (type === 'food') document.getElementById('tabFood').className = activeClass;
        if (type === 'beverage') document.getElementById('tabBeverage').className = activeClass;

        // Filter cards
        cards.forEach(card => {
          card.style.display = (type === '' || card.dataset.type === type) ? '' : 'none';
        });
      }

      // Search functionality
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('input', function(e) {
          const searchTerm = e.target.value.toLowerCase();
          const cards = document.querySelectorAll('.recipe-card');

          cards.forEach(card => {
            const name = card.dataset.name;
            if (name.includes(searchTerm)) {
              card.style.display = '';
            } else {
              card.style.display = 'none';
            }
          });
        });
      }

      // Close modals on Escape
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeModal();
          closeDeleteModal();
        }
      });

      // Close modals on outside click
      document.getElementById('recipeModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });

      document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
      });
    </script>
  @endpush
</x-app-layout>
