<div id="bookingInfoModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full">

    <!-- Header -->
    <div class="flex items-start justify-between p-6 pb-4 border-b border-gray-100">
      <div>
        <h3 class="text-lg font-bold text-gray-900">Info Booking</h3>
        <p id="biModalStatusBadge"
           class="mt-1"></p>
      </div>
      <button onclick="closeBookingInfoModal()"
              class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
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

    <!-- Info section -->
    <div class="px-6 pt-4 pb-2 space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Nama Booking</p>
          <p id="biModalBookingName"
             class="text-sm font-semibold text-gray-900 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Nama Customer</p>
          <p id="biModalCustomerName"
             class="text-sm font-semibold text-gray-900 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">No. HP</p>
          <p id="biModalPhone"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Meja</p>
          <p id="biModalTable"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Tanggal</p>
          <p id="biModalDate"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Jam</p>
          <p id="biModalTime"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">DP</p>
          <p id="biModalDownPayment"
             class="text-sm text-gray-700 mt-0.5">Rp 0</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Event</p>
          <p id="biModalEvent"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Min. Charge Event</p>
          <p id="biModalEventMinimumCharge"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
      </div>
      <div id="biModalNoteWrap"
           class="hidden">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Catatan</p>
        <p id="biModalNote"
           class="text-sm text-gray-600 mt-0.5"></p>
      </div>
    </div>

    <hr class="mx-6 border-gray-100 my-2">

    <!-- Status update section — only for confirmed -->
    <div id="biModalStatusForm"
         class="hidden px-6 pb-4">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Ubah Status</p>
      <form id="biStatusForm"
            method="POST">
        @csrf
        @method('PATCH')
        <div class="space-y-2">
          <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
            <input type="radio"
                   name="status"
                   value="pending"
                   class="w-4 h-4 text-slate-600 focus:ring-slate-500">
            <div class="ml-3 flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-700">Pending</span>
              <span class="text-sm text-gray-600">Kembalikan ke pending</span>
            </div>
          </label>
          <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
            <input type="radio"
                   name="status"
                   value="confirmed"
                   class="w-4 h-4 text-slate-600 focus:ring-slate-500">
            <div class="ml-3 flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-700">Confirmed</span>
              <span class="text-sm text-gray-600">Tetap confirmed</span>
            </div>
          </label>
          <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
            <input type="radio"
                   name="status"
                   value="checked_in"
                   class="w-4 h-4 text-slate-600 focus:ring-slate-500">
            <div class="ml-3 flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700">Checked-in</span>
              <span class="text-sm text-gray-600">Customer sudah datang</span>
            </div>
          </label>
          <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
            <input type="radio"
                   name="status"
                   value="cancelled"
                   class="w-4 h-4 text-slate-600 focus:ring-slate-500">
            <div class="ml-3 flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-700">Cancelled</span>
              <span class="text-sm text-gray-600">Batalkan booking</span>
            </div>
          </label>
          <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
            <input type="radio"
                   name="status"
                   value="rejected"
                   class="w-4 h-4 text-slate-600 focus:ring-slate-500">
            <div class="ml-3 flex items-center gap-2">
              <span class="px-2 py-0.5 text-xs font-medium rounded bg-orange-100 text-orange-700">Rejected</span>
              <span class="text-sm text-gray-600">Tolak booking</span>
            </div>
          </label>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button"
                  onclick="closeBookingInfoModal()"
                  class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
            Tutup
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition text-sm font-medium">
            Update Status
          </button>
        </div>
      </form>
    </div>

    <!-- Read-only footer — only for checked_in -->
    <div id="biModalReadOnlyFooter"
         class="hidden px-6 pb-5">
      <div class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-100 rounded-lg">
        <svg class="w-4 h-4 text-blue-400 shrink-0"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xs text-blue-700 font-medium">Customer sudah check-in. Status tidak bisa diubah.</p>
      </div>
      <div class="flex justify-end mt-3">
        <button type="button"
                onclick="closeBookingInfoModal()"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
          Tutup
        </button>
      </div>
    </div>

  </div>
</div>
