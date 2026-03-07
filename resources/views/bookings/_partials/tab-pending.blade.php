{{-- PENDING TAB --}}

{{-- Top bar: stats + date filter --}}
<div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
  <div class="flex items-center gap-3">
    <div class="bg-yellow-800 rounded-xl px-5 py-4 flex items-center gap-3">
      <div class="w-9 h-9 bg-yellow-600 rounded-lg flex items-center justify-center shrink-0">
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
        <div class="text-2xl font-bold text-white">{{ $pendingBookings }}</div>
        <div class="text-sm font-semibold text-yellow-200">Total Pending</div>
      </div>
    </div>
    @if (!empty($conflictingPendingKeys))
      <div class="bg-orange-800 rounded-xl px-5 py-4 flex items-center gap-3">
        <div class="w-9 h-9 bg-orange-600 rounded-lg flex items-center justify-center shrink-0">
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
          <div class="text-xl font-bold text-white">{{ count($conflictingPendingKeys) }}</div>
          <div class="text-sm font-semibold text-orange-200">Konflik Meja</div>
        </div>
      </div>
    @endif
  </div>

  {{-- Date range filter --}}
  <form method="GET"
        action="{{ route('admin.bookings.index') }}"
        class="flex items-center gap-2 flex-wrap">
    <input type="hidden"
           name="tab"
           value="pending">
    <input type="date"
           name="date_from"
           value="{{ request('date_from') }}"
           class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-white text-gray-700">
    <span class="text-gray-400 font-medium text-sm">–</span>
    <input type="date"
           name="date_to"
           value="{{ request('date_to') }}"
           class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-white text-gray-700">
    <button type="submit"
            class="px-3 py-2 text-xs font-medium bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition">Filter</button>
    @if (request('date_from') || request('date_to'))
      <a href="{{ route('admin.bookings.index', ['tab' => 'pending']) }}"
         class="px-3 py-2 text-xs font-medium text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">Reset</a>
    @endif
  </form>
</div>

{{-- Conflict / blocked notices --}}
@if (!empty($conflictingPendingKeys))
  <div class="flex items-center gap-3 px-4 py-3 bg-orange-50 border border-orange-200 rounded-xl mb-4 text-sm text-orange-800">
    <svg class="w-4 h-4 text-orange-500 shrink-0"
         fill="none"
         stroke="currentColor"
         viewBox="0 0 24 24">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <span>Terdapat <strong>{{ count($conflictingPendingKeys) }} slot meja</strong> yang dipesan oleh lebih dari satu customer. Baris bertanda
      <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-orange-200 text-orange-800 rounded text-xs font-semibold">⚠ Konflik</span>
      hanya bisa dikonfirmasi salah satunya — yang lain akan otomatis diblokir.
    </span>
  </div>
@endif

{{-- Pending bookings table --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
  @if ($bookings->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-gray-400">
      <svg class="w-12 h-12 mb-3 text-gray-300"
           fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm font-medium">Tidak ada booking pending</p>
      <p class="text-xs text-gray-400 mt-1">Semua booking sudah dikonfirmasi atau diselesaikan</p>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Customer</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Meja</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Tanggal & Waktu</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Catatan</th>
            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach ($bookings as $booking)
            @php
              $slotKey = $booking->table_id . '_' . $booking->reservation_date;
              $isConflict = in_array($slotKey, $conflictingPendingKeys);
              $isBlocked = in_array($slotKey, $blockedPendingKeys);
              $customerName = $booking->customer->profile->name ?? ($booking->customer->customerUser->name ?? ($booking->customer->name ?? '-'));
              $areaName = $booking->table?->area?->name ?? '';
              $areaBadge = match (true) {
                  str_contains(strtolower($areaName), 'room') || str_contains(strtolower($areaName), 'vip') => 'bg-purple-100 text-purple-700',
                  str_contains(strtolower($areaName), 'balcony') => 'bg-violet-100 text-violet-700',
                  str_contains(strtolower($areaName), 'lounge') => 'bg-cyan-100 text-cyan-700',
                  strlen($areaName) > 0 => 'bg-gray-100 text-gray-600',
                  default => 'bg-gray-100 text-gray-500',
              };
            @endphp
            <tr class="hover:bg-gray-50 transition-colors {{ $isConflict ? 'bg-orange-50 hover:bg-orange-100' : ($isBlocked ? 'bg-red-50 hover:bg-red-100' : '') }}">

              {{-- ID --}}
              <td class="px-4 py-4 whitespace-nowrap">
                <span class="text-sm font-mono font-semibold text-gray-500">
                  #{{ $booking->booking_code_formatted }}
                </span>
              </td>

              {{-- Customer --}}
              <td class="px-4 py-4">
                <div class="text-base font-semibold text-gray-900">{{ $customerName }}</div>
                @php
                  $phone = $booking->customer->profile?->phone ?? null;
                @endphp
                @if ($phone)
                  <div class="text-sm text-gray-400 mt-0.5">{{ $phone }}</div>
                @endif
              </td>

              {{-- Meja --}}
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-base font-semibold text-gray-900">{{ $booking->table?->table_number ?? '-' }}</div>
                @if ($areaName)
                  <span class="inline-block mt-1 text-sm font-medium px-2 py-0.5 rounded-full {{ $areaBadge }}">
                    {{ $areaName }}
                  </span>
                @endif
                {{-- Conflict badge --}}
                @if ($isBlocked)
                  <div class="mt-1.5">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 bg-red-100 text-red-700 rounded-full">
                      <svg class="w-3 h-3"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      Sudah dikonfirmasi customer lain
                    </span>
                  </div>
                @elseif ($isConflict)
                  <div class="mt-1.5">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full">
                      <svg class="w-3 h-3"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      ⚠ Konflik
                    </span>
                  </div>
                @endif
              </td>

              {{-- Date & Time --}}
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-base font-medium text-gray-900">
                  {{ \Carbon\Carbon::parse($booking->reservation_date)->format('d M Y') }}
                </div>
                <div class="text-sm text-gray-400 mt-0.5">
                  {{ $booking->reservation_time ? \Carbon\Carbon::parse($booking->reservation_time)->format('H:i') : '-' }}
                </div>
              </td>

              {{-- Note --}}
              <td class="px-4 py-4 max-w-[180px]">
                @if ($booking->note)
                  <p class="text-sm text-gray-500 line-clamp-2">{{ $booking->note }}</p>
                @else
                  <span class="text-sm text-gray-300">—</span>
                @endif
              </td>

              {{-- Actions --}}
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  @if (!$isBlocked)
                    {{-- Confirm --}}
                    <form method="POST"
                          action="{{ route('admin.bookings.updateStatus', $booking) }}"
                          class="inline">
                      @csrf
                      @method('PATCH')
                      <input type="hidden"
                             name="status"
                             value="confirmed">
                      <button type="submit"
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 transition"
                              onclick="return confirm('Konfirmasi booking ini? Meja akan ditandai sebagai Booked.')">
                        <svg class="w-3.5 h-3.5"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                          <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Konfirmasi
                      </button>
                    </form>
                  @endif

                  {{-- Reject --}}
                  <form method="POST"
                        action="{{ route('admin.bookings.updateStatus', $booking) }}"
                        class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden"
                           name="status"
                           value="rejected">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition"
                            onclick="return confirm('Tolak booking ini?')">
                      <svg class="w-3.5 h-3.5"
                           fill="none"
                           stroke="currentColor"
                           viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Tolak
                    </button>
                  </form>

                  {{-- Edit / ubah status --}}
                  <button onclick="openStatusModal({{ $booking->id }}, 'pending')"
                          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                    <svg class="w-3.5 h-3.5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                      <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Ubah
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
