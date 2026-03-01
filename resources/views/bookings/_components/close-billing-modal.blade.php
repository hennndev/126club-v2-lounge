<!-- Close Billing Modal -->
<div id="closeBillingModal"
     class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50 hidden">
  <div class="bg-white w-full sm:max-w-md sm:mx-4 rounded-t-2xl sm:rounded-2xl shadow-xl"
       onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-gray-900 text-sm">Tutup Billing</h3>
          <p class="text-xs text-gray-500 mt-0.5"
             id="cbModalSubtitle">Konfirmasi pembayaran customer</p>
        </div>
      </div>
      <button onclick="closeCloseBillingModal()"
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

    <!-- Body -->
    <div class="px-5 py-4 space-y-4">

      <!-- Billing Summary -->
      <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
        <div class="flex justify-between text-gray-600">
          <span>Minimum Charge</span>
          <span id="cbMinimumCharge">Rp 0</span>
        </div>
        <div class="flex justify-between text-gray-600">
          <span>Orders Total</span>
          <span id="cbOrdersTotal">Rp 0</span>
        </div>
        <div class="flex justify-between text-gray-600"
             id="cbDiscountRow"
             style="display:none!important">
          <span>Diskon</span>
          <span id="cbDiscount"
                class="text-green-600">- Rp 0</span>
        </div>
        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold text-gray-900 text-base">
          <span>TOTAL</span>
          <span id="cbGrandTotal">Rp 0</span>
        </div>
      </div>

      <!-- Minimum charge warning -->
      <div id="cbMinWarning"
           class="hidden flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-xs text-amber-700"
           id="cbMinWarningText"></p>
      </div>

      <!-- Payment method -->
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Metode Pembayaran</label>
        <div class="grid grid-cols-3 gap-2">
          @foreach (['cash' => 'Tunai', 'kredit' => 'Kredit', 'debit' => 'Debit'] as $val => $label)
            <label class="flex flex-col items-center gap-1.5 p-3 rounded-xl border cursor-pointer
                          has-[:checked]:border-green-500 has-[:checked]:bg-green-50 border-gray-200 hover:border-gray-300 transition">
              <input type="radio"
                     name="cb_payment_method"
                     value="{{ $val }}"
                     class="sr-only"
                     {{ $val === 'cash' ? 'checked' : '' }}>
              <svg class="w-5 h-5 text-gray-500 has-peer-checked:text-green-600"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                @if ($val === 'cash')
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                @elseif ($val === 'kredit')
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                @else
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                @endif
              </svg>
              <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex gap-3 px-5 pb-5">
      <button onclick="closeCloseBillingModal()"
              class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition">
        Batal
      </button>
      <button id="cbSubmitBtn"
              onclick="submitCloseBilling()"
              class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-500 font-semibold text-sm transition flex items-center justify-center gap-2">
        <svg class="w-4 h-4"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
          <path stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7" />
        </svg>
        Tutup & Cetak Struk
      </button>
    </div>
  </div>
</div>

<script>
  let closeBillingBookingId = null;

  function openCloseBillingModal(bookingId, minimumCharge, ordersTotal, discountAmount, grandTotal) {
    closeBillingBookingId = bookingId;

    const fmt = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v || 0);

    document.getElementById('cbMinimumCharge').textContent = fmt(minimumCharge);
    document.getElementById('cbOrdersTotal').textContent = fmt(ordersTotal);
    document.getElementById('cbGrandTotal').textContent = fmt(grandTotal);

    const discountRow = document.getElementById('cbDiscountRow');
    if (discountAmount > 0) {
      document.getElementById('cbDiscount').textContent = '- ' + fmt(discountAmount);
      discountRow.style.removeProperty('display');
    } else {
      discountRow.style.setProperty('display', 'none', 'important');
    }

    // Minimum charge warning
    const warning = document.getElementById('cbMinWarning');
    if (minimumCharge > 0 && ordersTotal < minimumCharge) {
      document.getElementById('cbMinWarningText').textContent =
        'Orders total belum memenuhi minimum charge. Selisih: ' + fmt(minimumCharge - ordersTotal);
      warning.classList.remove('hidden');
    } else {
      warning.classList.add('hidden');
    }

    // Reset payment method to cash
    document.querySelector('input[name="cb_payment_method"][value="cash"]').checked = true;

    document.getElementById('closeBillingModal').classList.remove('hidden');
  }

  function closeCloseBillingModal() {
    document.getElementById('closeBillingModal').classList.add('hidden');
    closeBillingBookingId = null;
  }

  async function submitCloseBilling() {
    if (!closeBillingBookingId) {
      return;
    }

    const paymentMethod = document.querySelector('input[name="cb_payment_method"]:checked')?.value;
    if (!paymentMethod) {
      return;
    }

    const btn = document.getElementById('cbSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memproses...';

    try {
      const res = await fetch(`/admin/bookings/${closeBillingBookingId}/close-billing`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          payment_method: paymentMethod
        }),
      });

      const data = await res.json();

      if (data.success) {
        closeCloseBillingModal();
        // Open receipt in new tab
        if (data.receipt_url) {
          window.open(data.receipt_url, '_blank');
        }
        // Reload page to update table map
        window.location.reload();
      } else {
        alert(data.message || 'Gagal menutup billing.');
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Tutup & Cetak Struk';
      }
    } catch (e) {
      alert('Terjadi kesalahan. Silakan coba lagi.');
      btn.disabled = false;
      btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Tutup & Cetak Struk';
    }
  }

  // Close on backdrop click
  document.getElementById('closeBillingModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeCloseBillingModal();
    }
  });
</script>
