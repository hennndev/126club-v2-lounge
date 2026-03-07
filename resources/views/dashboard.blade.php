<x-app-layout>
  <div class="p-6">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-8 mb-6">
      <h1 class="text-3xl font-bold text-white mb-2">Dashboard 126 Club</h1>
      <p class="text-slate-300">Ringkasan sistem manajemen POS dan booking</p>
    </div>

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Pendapatan Hari Ini -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-600">Pendapatan Hari Ini</h3>
          <div class="bg-green-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-green-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="mb-1">
          <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($revenueToday, 0, ',', '.') }}</p>
        </div>
        <p class="text-sm text-gray-500">{{ $transactionsToday }} transaksi</p>
      </div>

      <!-- Transaksi Hari Ini -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-600">Transaksi Hari Ini</h3>
          <div class="bg-slate-700 p-2 rounded-lg">
            <svg class="w-5 h-5 text-white"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <div class="mb-1">
          <p class="text-2xl font-bold text-gray-800">{{ $transactionsToday }}</p>
        </div>
        <p class="text-sm text-gray-500">{{ $itemsSoldToday }} item terjual</p>
      </div>

      <!-- Booking Pending -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-600">Booking Pending</h3>
          <div class="bg-orange-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-orange-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="mb-1">
          <p class="text-2xl font-bold text-gray-800">{{ $bookingPending }}</p>
        </div>
        <p class="text-sm text-gray-500">{{ $bookingConfirmed }} confirmed</p>
      </div>

      <!-- Meja Tersedia -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-600">Meja Tersedia</h3>
          <div class="bg-blue-100 p-2 rounded-lg">
            <svg class="w-5 h-5 text-blue-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="mb-1">
          <p class="text-2xl font-bold text-gray-800">{{ $availableTables }}/{{ $totalTables }}</p>
        </div>
        <p class="text-sm text-gray-500">meja siap digunakan</p>
      </div>
    </div>

    <!-- Bottom Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Status Booking -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-2 mb-6">
          <svg class="w-5 h-5 text-gray-600"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h2 class="text-lg font-semibold text-gray-800">Status Booking</h2>
        </div>
        <p class="text-sm text-gray-500 mb-6">Ringkasan booking berdasarkan status</p>

        <div class="space-y-3">
          <!-- Pending -->
          <div class="flex items-center justify-between p-4 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-orange-500 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
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
                <p class="font-semibold text-gray-800">Pending</p>
                <p class="text-sm text-gray-600">Menunggu konfirmasi</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-orange-600">{{ $bookingPending }}</div>
          </div>

          <!-- Confirmed -->
          <div class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-blue-500 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
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
                <p class="font-semibold text-gray-800">Confirmed</p>
                <p class="text-sm text-gray-600">Sudah dikonfirmasi</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-blue-600">{{ $bookingConfirmed }}</div>
          </div>

          <!-- Completed -->
          <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-green-500 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-800">Completed</p>
                <p class="text-sm text-gray-600">Sudah selesai</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-green-600">{{ $bookingCompleted }}</div>
          </div>
        </div>
      </div>

      <!-- Status Produk -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <div class="flex items-center space-x-2 mb-6">
          <svg class="w-5 h-5 text-gray-600"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <h2 class="text-lg font-semibold text-gray-800">Status Produk</h2>
        </div>
        <p class="text-sm text-gray-500 mb-6">Informasi stok dan inventori</p>

        <div class="space-y-3">
          <!-- Total Produk -->
          <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-slate-700 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
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
                <p class="font-semibold text-gray-800">Total Produk</p>
                <p class="text-sm text-gray-600">Semua produk</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-slate-700">{{ $totalProducts }}</div>
          </div>

          <!-- Stok Rendah -->
          <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-yellow-500 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-800">Stok Rendah</p>
                <p class="text-sm text-gray-600">&lt; 10 item</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-yellow-600">{{ $lowStockCount }}</div>
          </div>

          <!-- Habis -->
          <div class="flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="bg-red-500 p-2 rounded-lg">
                <svg class="w-5 h-5 text-white"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-800">Habis</p>
                <p class="text-sm text-gray-600">Stok = 0</p>
              </div>
            </div>
            <div class="text-3xl font-bold text-red-600">{{ $outOfStockCount }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
