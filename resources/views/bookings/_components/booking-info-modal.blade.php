<div id="bookingInfoModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
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
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Dibuat Oleh</p>
          <p id="biModalCreatedBy"
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
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
        <div>
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Event</p>
          <p id="biModalEvent"
             class="text-sm text-gray-700 mt-0.5">—</p>
        </div>
      </div>

      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Minimum Charge Event</p>
        <p id="biModalEventMinimumCharge"
           class="text-sm text-gray-700 mt-0.5">—</p>
      </div>

      <div id="biModalNoteWrap"
           class="hidden">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Catatan</p>
        <p id="biModalNote"
           class="text-sm text-gray-700 mt-0.5 whitespace-pre-line">—</p>
      </div>
    </div>

    <div id="biModalReadOnlyFooter"
         class="px-6 pb-5">
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
