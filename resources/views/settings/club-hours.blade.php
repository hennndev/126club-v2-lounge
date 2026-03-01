<x-app-layout>
  <div class="p-6">

    <!-- Back -->
    <a href="{{ route('admin.settings.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-800 mb-6">
      <svg class="w-4 h-4"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Kembali ke Menu Pengaturan
    </a>

    @if (session('success'))
      <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
        {{ session('success') }}
      </div>
    @endif

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Jam Operasional</h1>
      <p class="text-sm text-slate-500 mt-1">Atur jam buka dan tutup club untuk setiap hari dalam seminggu</p>
    </div>

    <!-- Info Box -->
    <div class="bg-teal-50 border border-teal-200 rounded-xl p-4 mb-6 flex items-start gap-3">
      <svg class="w-5 h-5 text-teal-500 flex-shrink-0 mt-0.5"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-teal-700">
        Centang hari yang <strong>aktif beroperasi</strong> dan isi jam buka/tutup. Kosongkan jam jika tidak ingin menampilkan waktu.
        Anda dapat mengubah jam tutup sewaktu-waktu langsung dari halaman ini.
      </p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.settings.club-hours.update') }}"
          method="POST">
      @csrf
      @method('PUT')

      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
        <!-- Table Header -->
        <div class="grid grid-cols-12 gap-4 px-5 py-3 bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wide">
          <div class="col-span-1">Buka</div>
          <div class="col-span-3">Hari</div>
          <div class="col-span-4">Jam Buka</div>
          <div class="col-span-4">Jam Tutup</div>
        </div>

        @php
          $dayOrder = [1, 2, 3, 4, 5, 6, 0]; // Mon–Sun
          $dayNames = \App\Models\ClubOperatingHour::$dayNames;
        @endphp

        @foreach ($dayOrder as $index => $dayNum)
          @php $hour = $hours->get($dayNum); @endphp
          <div class="grid grid-cols-12 gap-4 items-center px-5 py-4 {{ $index < count($dayOrder) - 1 ? 'border-b border-slate-100' : '' }}">

            <input type="hidden"
                   name="hours[{{ $index }}][day_of_week]"
                   value="{{ $dayNum }}">

            <!-- Toggle -->
            <div class="col-span-1 flex justify-center">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="hidden"
                       name="hours[{{ $index }}][is_open]"
                       value="0">
                <input type="checkbox"
                       name="hours[{{ $index }}][is_open]"
                       value="1"
                       class="sr-only peer"
                       onchange="toggleRow(this, {{ $dayNum }})"
                       {{ $hour?->is_open ?? true ? 'checked' : '' }}>
                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-500"></div>
              </label>
            </div>

            <!-- Day Name -->
            <div class="col-span-3">
              <span class="font-semibold text-slate-800 text-sm">{{ $dayNames[$dayNum] }}</span>
              @if ($dayNum === now('Asia/Jakarta')->dayOfWeek)
                <span class="ml-2 px-1.5 py-0.5 bg-teal-100 text-teal-700 text-xs font-semibold rounded-full">Hari ini</span>
              @endif
            </div>

            <!-- Open Time -->
            <div class="col-span-4"
                 id="open-row-{{ $dayNum }}">
              <input type="time"
                     name="hours[{{ $index }}][open_time]"
                     value="{{ $hour?->open_time ? \Carbon\Carbon::parse($hour->open_time)->format('H:i') : '' }}"
                     class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 {{ !($hour?->is_open ?? true) ? 'opacity-40 pointer-events-none' : '' }}">
            </div>

            <!-- Close Time -->
            <div class="col-span-4"
                 id="close-row-{{ $dayNum }}">
              <input type="time"
                     name="hours[{{ $index }}][close_time]"
                     value="{{ $hour?->close_time ? \Carbon\Carbon::parse($hour->close_time)->format('H:i') : '' }}"
                     class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-400 focus:border-teal-400 {{ !($hour?->is_open ?? true) ? 'opacity-40 pointer-events-none' : '' }}">
            </div>

          </div>
        @endforeach
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-teal-600 hover:bg-teal-500 text-white font-semibold text-sm rounded-xl transition">
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7" />
          </svg>
          Simpan Jam Operasional
        </button>
      </div>
    </form>
  </div>

  <script>
    function toggleRow(checkbox, dayNum) {
      const isOpen = checkbox.checked;
      const openInput = document.querySelector(`#open-row-${dayNum} input`);
      const closeInput = document.querySelector(`#close-row-${dayNum} input`);

      [openInput, closeInput].forEach(el => {
        if (isOpen) {
          el.classList.remove('opacity-40', 'pointer-events-none');
        } else {
          el.classList.add('opacity-40', 'pointer-events-none');
        }
      });
    }
  </script>
</x-app-layout>
