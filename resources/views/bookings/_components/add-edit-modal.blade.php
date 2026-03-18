@php
  $availableTablesCount = $tables->where('is_active', true)->count();
@endphp

<div id="bookingModal"
     class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
     x-data="bookingModal()">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

    <!-- Modal Header -->
    <div class="flex items-start justify-between px-6 py-5 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-slate-800 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-white"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h3 id="modalTitle"
              class="text-lg font-bold text-gray-900">Booking Baru</h3>
          <p class="text-xs text-gray-400 mt-0.5">Pilih kategori, meja, dan lengkapi data customer untuk membuat reservasi baru</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500 font-medium">{{ $availableTablesCount }} meja tersedia</span>
        <button type="button"
                onclick="closeModal()"
                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition">
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

    <form id="bookingForm"
          method="POST"
          action="{{ route('admin.bookings.store') }}"
          class="px-6 py-5 space-y-5">
      @csrf
      <input type="hidden"
             name="_method"
             value="POST"
             id="formMethod">
      <input type="hidden"
             name="table_id"
             id="table_id">
      <input type="hidden"
             name="status"
             id="status"
             value="pending">

      <!-- Meja yang Dipilih -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">
          Meja yang Dipilih <span class="text-red-500">*</span>
          <span class="ml-2 text-xs font-normal px-2 py-0.5 bg-blue-100 text-blue-600 rounded-full">Quick Booking</span>
        </label>

        <!-- Picker when nothing selected -->
        <div x-show="!selectedTable"
             class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto pr-1">
          @foreach ($tables as $table)
            @php
              $hasActiveBooking = collect($activeBookingsByTable ?? collect())->has($table->id);
              $hasActiveSession = collect($activeSessions ?? collect())->contains(fn($session) => (int) $session->table_id === (int) $table->id && $session->status === 'active');
              $isOccupied = $hasActiveBooking || $hasActiveSession;
            @endphp
            <button type="button"
                    @if (!$isOccupied) @click="selectTable({{ json_encode(['id' => $table->id, 'table_number' => $table->table_number, 'capacity' => $table->capacity, 'minimum_charge' => $table->minimum_charge, 'area_name' => $table->area->name ?? '']) }})" @endif
                    class="text-left px-3 py-2.5 rounded-lg border text-xs transition
                           {{ $isOccupied ? 'border-gray-200 bg-gray-50 opacity-50 cursor-not-allowed' : 'border-gray-200 hover:border-blue-400 hover:bg-blue-50 cursor-pointer' }}">
              <div class="flex items-center justify-between mb-0.5">
                <span class="font-semibold text-gray-800 truncate pr-1">{{ $table->table_number }}</span>
                @if ($isOccupied)
                  <span class="text-red-400 shrink-0">•Busy</span>
                @else
                  <span class="text-green-500 shrink-0">•Free</span>
                @endif
              </div>
              <span class="text-gray-400">{{ $table->area->name ?? '' }} · {{ $table->capacity }} pax</span>
            </button>
          @endforeach
        </div>

        <!-- Selected table card -->
        <div x-show="selectedTable"
             x-cloak
             class="rounded-xl border-2 border-blue-400 bg-blue-50 p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M3 14h18M10 4v16M14 4v16" />
              </svg>
              <span class="font-bold text-blue-800"
                    x-text="selectedTable?.table_number"></span>
            </div>
            <button type="button"
                    @click="clearTable()"
                    class="text-xs text-blue-500 hover:text-blue-700 underline">Ganti</button>
          </div>
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
              <span class="text-blue-500">Kapasitas:</span>
              <span class="text-blue-800 font-semibold ml-1"
                    x-text="(selectedTable?.capacity ?? '') + ' orang'"></span>
            </div>
            <div>
              <span class="text-blue-500">Min Charge:</span>
              <span class="text-blue-800 font-semibold ml-1"
                    x-text="selectedTable?.minimum_charge ? 'Rp ' + Number(selectedTable.minimum_charge).toLocaleString('id-ID') : '-'"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer -->
      <div>
        <label for="booking_name"
               class="block text-sm font-semibold text-gray-700 mb-2">
          Nama Booking <span class="text-gray-400 font-normal text-xs">(opsional — a.n. siapa reservasi ini)</span>
        </label>
        <input type="text"
               name="booking_name"
               id="booking_name"
               placeholder="Contoh: a.n. Budi Santoso"
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
      </div>

      <!-- Customer -->
      <div>
        <label for="customer_id"
               class="block text-sm font-semibold text-gray-700 mb-2">
          Customer <span class="text-red-500">*</span>
        </label>
        <select name="customer_id"
                id="customer_id"
                required
                @change="selectCustomer($event.target.value)"
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
          <option value="">Pilih customer</option>
          @foreach ($customers as $customer)
            @php
              $hasActiveSession = collect($activeSessionCustomerIds ?? [])->contains($customer->id);
            @endphp
            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->profile?->phone ? ' – ' . $customer->profile->phone : '' }}{{ $hasActiveSession ? ' (Sedang check-in)' : '' }}</option>
          @endforeach
        </select>
      </div>

      <!-- Phone + Email -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">No. Telepon <span class="text-red-500">*</span></label>
          <input type="text"
                 name="phone"
                 id="phone"
                 x-model="phoneValue"
                 placeholder="08xx-xxxx-xxxx"
                 class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Email (Opsional)</label>
          <input type="email"
                 name="email"
                 id="email"
                 x-model="emailValue"
                 placeholder="customer@email.com"
                 class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
        </div>
      </div>

      <!-- Tanggal + Waktu -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Tanggal <span class="text-red-500">*</span>
            <span class="ml-1 text-xs font-normal px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded">Realtime</span>
          </label>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <input type="date"
                   name="reservation_date"
                   id="reservation_date"
                   required
                   :value="today"
                   class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Waktu <span class="text-red-500">*</span>
            <span class="ml-1 text-xs font-normal px-1.5 py-0.5 bg-blue-100 text-blue-600 rounded">Realtime</span>
          </label>
          <input type="time"
                 name="reservation_time"
                 id="reservation_time"
                 required
                 :value="currentTime"
                 class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
        </div>
      </div>

      <!-- Jumlah Tamu -->
      <div>
        <label for="guest_count"
               class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Tamu</label>
        <input type="number"
               name="guest_count"
               id="guest_count"
               min="1"
               :value="selectedTable?.capacity ?? ''"
               placeholder="Jumlah tamu"
               class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white">
      </div>

      <!-- Catatan -->
      <div>
        <label for="note"
               class="block text-sm font-semibold text-gray-700 mb-2">Catatan (Opsional)</label>
        <textarea name="note"
                  id="note"
                  rows="3"
                  placeholder="Permintaan khusus, preference makanan/minuman, dll"
                  class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white resize-none"></textarea>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 pt-2">
        <button type="button"
                onclick="closeModal()"
                class="px-5 py-2.5 text-sm font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
          Batal
        </button>
        <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-slate-800 hover:bg-slate-900 text-white rounded-lg transition">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Buat Booking
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  const bookingActiveSessionCustomerIds = @json($activeSessionCustomerIds ?? []);

  const bookingCustomers = {!! json_encode(
      $customers->map(
              fn($c) => [
                  'id' => $c->id,
                  'phone' => $c->profile?->phone ?? '',
                  'email' => $c->email ?? '',
              ],
          )->values(),
  ) !!};

  function bookingModal() {
    const now = new Date();
    const dateParts = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Jakarta',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).formatToParts(now).reduce((parts, part) => {
      if (part.type !== 'literal') {
        parts[part.type] = part.value;
      }

      return parts;
    }, {});

    const today = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
    const currentTime = new Intl.DateTimeFormat('en-GB', {
      timeZone: 'Asia/Jakarta',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(now);

    return {
      selectedTable: null,
      today,
      currentTime,
      phoneValue: '',
      emailValue: '',

      init() {
        document.addEventListener('table-selected', e => {
          this.selectTable(e.detail);
        });

        const bookingForm = document.getElementById('bookingForm');

        bookingForm?.addEventListener('submit', e => {
          const selectedCustomerId = document.getElementById('customer_id')?.value;
          const selectedId = Number(selectedCustomerId || 0);

          if (this.isCreateMode() && selectedId > 0 && bookingActiveSessionCustomerIds.includes(selectedId)) {
            e.preventDefault();
            alert('Customer sedang check-in di meja lain dan tidak bisa dibuat booking baru.');
          }
        });
      },

      isCreateMode() {
        return document.getElementById('formMethod')?.value === 'POST';
      },

      selectTable(table) {
        this.selectedTable = table;
        document.getElementById('table_id').value = table.id;
        const guestEl = document.getElementById('guest_count');
        if (guestEl && !guestEl.value) {
          guestEl.value = table.capacity;
        }
      },

      clearTable() {
        this.selectedTable = null;
        document.getElementById('table_id').value = '';
      },

      selectCustomer(id) {
        const selectedId = Number(id || 0);

        if (this.isCreateMode() && selectedId > 0 && bookingActiveSessionCustomerIds.includes(selectedId)) {
          const customerEl = document.getElementById('customer_id');

          if (customerEl) {
            customerEl.value = '';
          }

          this.phoneValue = '';
          this.emailValue = '';
          alert('Customer sedang check-in di meja lain dan tidak bisa dibuat booking baru.');

          return;
        }

        const customer = bookingCustomers.find(c => String(c.id) === String(id));
        if (customer) {
          this.phoneValue = customer.phone || '';
          this.emailValue = customer.email || '';
        } else {
          this.phoneValue = '';
          this.emailValue = '';
        }
      },
    };
  }
</script>
