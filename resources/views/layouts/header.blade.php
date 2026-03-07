<header class="bg-white border-b border-gray-200 px-6 py-4">
  <div class="flex items-center justify-between">
    <div class="flex items-center space-x-3">
      <!-- Sidebar Toggle -->
      <button @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)"
              class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
              :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'">
        <svg class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div class="bg-slate-800 rounded-lg p-2">
        <svg class="w-5 h-5 text-white"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <h1 class="text-xl font-bold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center space-x-4">
      <!-- User Menu -->
      <div class="relative">
        <button class="flex items-center space-x-3 hover:bg-gray-100 rounded-lg px-3 py-2">
          <div class="text-right">
            <p class="text-sm font-semibold text-gray-700">{{ Auth::user()->name }}</p>
            <p class="text-xs text-gray-500">Administrator</p>
          </div>
          <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
          </div>
        </button>
      </div>

      <!-- Logout -->
      <button type="button"
              onclick="document.getElementById('logoutModal').classList.remove('hidden')"
              class="p-2 text-gray-500 hover:bg-red-50 hover:text-red-500 rounded-lg transition-colors"
              title="Logout">
        <svg class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
    </div>
  </div>
</header>

<!-- Logout Confirmation Modal -->
<div id="logoutModal"
     class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
    <div class="flex items-center justify-center w-14 h-14 mx-auto bg-red-100 rounded-full mb-4">
      <svg class="w-7 h-7 text-red-600"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
      </svg>
    </div>
    <h3 class="text-lg font-bold text-gray-900 text-center mb-1">Keluar dari sistem?</h3>
    <p class="text-sm text-gray-500 text-center mb-6">Sesi kamu akan diakhiri dan kamu perlu login kembali.</p>
    <div class="flex gap-3">
      <button type="button"
              onclick="document.getElementById('logoutModal').classList.add('hidden')"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition">
        Batal
      </button>
      <form method="POST"
            action="{{ route('logout') }}"
            class="flex-1">
        @csrf
        <button type="submit"
                class="w-full px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 font-semibold transition">
          Keluar
        </button>
      </form>
    </div>
  </div>
</div>
