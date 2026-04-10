<div id="roleModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
      <h3 id="modalTitle"
          class="text-xl font-semibold text-gray-900">Tambah Role</h3>
      <button onclick="closeModal()"
              class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <form id="roleForm"
          method="POST"
          action="{{ route('admin.roles.store') }}">
      <input type="hidden"
             name="_token"
             id="roleFormToken">
      <input type="hidden"
             name="_method"
             value="POST"
             id="formMethod">

      <div class="p-6 space-y-6">
        <!-- Role Name -->
        <div>
          <label for="name"
                 class="block text-sm font-medium text-gray-700 mb-2">Nama Role *</label>
          <input type="text"
                 name="name"
                 id="name"
                 required
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>

        <!-- Description -->
        <div>
          <label for="description"
                 class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
          <textarea name="description"
                    id="description"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
        </div>

        <div>
          <label for="default_redirect_route"
                 class="block text-sm font-medium text-gray-700 mb-2">Default Redirect Halaman</label>
          <select name="default_redirect_route"
                  id="default_redirect_route"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white">
            <option value="">Tidak diatur (ikuti default sistem)</option>
            @foreach ($redirectOptions as $routeName => $routeLabel)
              <option value="{{ $routeName }}">{{ $routeLabel }}</option>
            @endforeach
          </select>
          <p class="text-xs text-gray-500 mt-1">User yang sudah login dan membuka halaman login akan diarahkan ke halaman ini sesuai role.</p>
        </div>

        <!-- Permissions -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
          <div class="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto space-y-2">
            @foreach ($permissions as $permission)
              <label class="flex items-center gap-2 hover:bg-gray-50 p-2 rounded cursor-pointer">
                <input type="checkbox"
                       name="permissions[]"
                       value="{{ $permission->id }}"
                       class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                <span class="text-sm text-gray-700">{{ $permission->name }}</span>
              </label>
            @endforeach
          </div>
        </div>
      </div>

      <div class="p-6 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
        <button type="button"
                onclick="closeModal()"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
          Batal
        </button>
        <button type="submit"
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>
