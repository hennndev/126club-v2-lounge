<!-- Close Billing Modal -->
@php $generalSettings = \App\Models\GeneralSetting::instance(); @endphp
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
        <div class="flex justify-between text-gray-600"
             id="cbServiceChargeRow">
          <span id="cbServiceChargeLabel">Service Charge ({{ $generalSettings->service_charge_percentage }}%)</span>
          <span id="cbServiceCharge">Rp 0</span>
        </div>
        <div class="flex justify-between text-gray-600"
             id="cbTaxRow">
          <span id="cbTaxLabel">PPN ({{ $generalSettings->tax_percentage }}%)</span>
          <span id="cbTax">Rp 0</span>
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

      <div id="cbCheckerWarning"
           class="hidden flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
          <svg class="h-4 w-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-amber-900">Transaction Checker belum lengkap</p>
          <p class="mt-1 text-xs text-amber-800"
             id="cbCheckerProgressText"></p>
          <p class="mt-1 text-xs text-amber-700"
             id="cbCheckerWarningText"></p>
        </div>
      </div>

      <!-- Payment mode -->
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">Mode Pembayaran</label>
        <div class="grid grid-cols-2 gap-2">
          @foreach (['normal' => 'Payment Biasa', 'split' => 'Split Bill'] as $val => $label)
            <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer
                          has-[:checked]:border-green-500 has-[:checked]:bg-green-50 border-gray-200 hover:border-gray-300 transition">
              <input type="radio"
                     name="cb_payment_mode"
                     value="{{ $val }}"
                     class="sr-only"
                     {{ $val === 'normal' ? 'checked' : '' }}>
              <span class="text-xs font-semibold text-gray-700">{{ $label }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <!-- Payment method (normal mode) -->
      <div id="cbNormalMethodBlock">
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
              <svg class="w-5 h-5 text-gray-500"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                @if ($val === 'cash')
                  <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
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

      <!-- Split mode: cash + debit -->
      <div id="cbSplitBlock"
           class="hidden space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="cb_split_cash"
                   class="block text-xs font-semibold text-gray-600 mb-1.5">Cash</label>
            <input id="cb_split_cash_display"
                   type="text"
                   inputmode="numeric"
                   value="Rp 0"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
            <input id="cb_split_cash"
                   type="hidden"
                   value="0">
          </div>
          <div>
            <label for="cb_split_debit"
                   class="block text-xs font-semibold text-gray-600 mb-1.5">Debit</label>
            <input id="cb_split_debit_display"
                   type="text"
                   inputmode="numeric"
                   value="Rp 0"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent">
            <input id="cb_split_debit"
                   type="hidden"
                   value="0">
          </div>
        </div>
        <div id="cbSplitSummary"
             class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 space-y-1">
          <div class="flex items-center justify-between">
            <span>Total Split</span>
            <span id="cbSplitTotal">Rp 0</span>
          </div>
          <div class="flex items-center justify-between font-semibold">
            <span>Sisa / Selisih</span>
            <span id="cbSplitDiff">Rp 0</span>
          </div>
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
  const cbServiceChargePercentage = {{ $generalSettings->service_charge_percentage }};
  const cbTaxPercentage = {{ $generalSettings->tax_percentage }};

  let closeBillingBookingId = null;
  let cbCurrentGrandTotal = 0;
  let cbCheckerIncomplete = false;

  function formatRupiah(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
  }

  function extractNumber(inputValue) {
    const digits = String(inputValue || '').replace(/[^0-9]/g, '');
    return digits ? Number(digits) : 0;
  }

  function setSplitInput(which, amount) {
    const hidden = document.getElementById(`cb_split_${which}`);
    const display = document.getElementById(`cb_split_${which}_display`);
    hidden.value = String(amount || 0);
    display.value = formatRupiah(amount || 0);
  }

  function onSplitInput(which, event) {
    const amount = extractNumber(event.target.value);
    setSplitInput(which, amount);
    updateSplitSummary();
  }

  function updateCloseBillingSubmitButton() {
    const btn = document.getElementById('cbSubmitBtn');

    if (!btn) {
      return;
    }

    btn.disabled = cbCheckerIncomplete;
    btn.classList.toggle('bg-green-600', !cbCheckerIncomplete);
    btn.classList.toggle('hover:bg-green-500', !cbCheckerIncomplete);
    btn.classList.toggle('text-white', !cbCheckerIncomplete);
    btn.classList.toggle('bg-gray-300', cbCheckerIncomplete);
    btn.classList.toggle('text-gray-600', cbCheckerIncomplete);
    btn.classList.toggle('cursor-not-allowed', cbCheckerIncomplete);

    btn.innerHTML = cbCheckerIncomplete ?
      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg> Selesaikan Checker Dulu' :
      '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Tutup & Cetak Struk';
  }

  function openCloseBillingModal(trigger) {
    const bookingId = Number(trigger?.dataset?.bookingId || 0);
    const minimumCharge = Number(trigger?.dataset?.minimumCharge || 0);
    const ordersTotal = Number(trigger?.dataset?.ordersTotal || 0);
    const discountAmount = Number(trigger?.dataset?.discountAmount || 0);
    const checkerChecked = Number(trigger?.dataset?.checkerChecked || 0);
    const checkerTotal = Number(trigger?.dataset?.checkerTotal || 0);

    closeBillingBookingId = bookingId;

    const fmt = v => formatRupiah(v || 0);

    document.getElementById('cbMinimumCharge').textContent = fmt(minimumCharge);
    document.getElementById('cbOrdersTotal').textContent = fmt(ordersTotal);

    const discountRow = document.getElementById('cbDiscountRow');
    if (discountAmount > 0) {
      document.getElementById('cbDiscount').textContent = '- ' + fmt(discountAmount);
      discountRow.style.removeProperty('display');
    } else {
      discountRow.style.setProperty('display', 'none', 'important');
    }

    // Compute subtotal after discount
    const subtotal = Math.max(minimumCharge, ordersTotal);
    const afterDiscount = subtotal - discountAmount;

    // Service charge
    const serviceChargeAmount = Math.round(afterDiscount * cbServiceChargePercentage / 100);
    document.getElementById('cbServiceCharge').textContent = fmt(serviceChargeAmount);

    // Tax
    const taxAmount = Math.round((afterDiscount + serviceChargeAmount) * cbTaxPercentage / 100);
    document.getElementById('cbTax').textContent = fmt(taxAmount);

    // Grand total
    const computedGrandTotal = afterDiscount + serviceChargeAmount + taxAmount;
    cbCurrentGrandTotal = computedGrandTotal;
    document.getElementById('cbGrandTotal').textContent = fmt(computedGrandTotal);

    // Minimum charge warning
    const warning = document.getElementById('cbMinWarning');
    if (minimumCharge > 0 && ordersTotal < minimumCharge) {
      document.getElementById('cbMinWarningText').textContent =
        'Orders total belum memenuhi minimum charge. Selisih: ' + fmt(minimumCharge - ordersTotal);
      warning.classList.remove('hidden');
    } else {
      warning.classList.add('hidden');
    }

    const checkerWarning = document.getElementById('cbCheckerWarning');
    cbCheckerIncomplete = checkerTotal > 0 && checkerChecked < checkerTotal;

    if (cbCheckerIncomplete) {
      const checkerRemaining = checkerTotal - checkerChecked;
      document.getElementById('cbCheckerProgressText').textContent =
        `${checkerChecked} dari ${checkerTotal} item sudah dichecklist.`;
      document.getElementById('cbCheckerWarningText').textContent =
        `Masih ada ${checkerRemaining} item yang belum dichecklist di Transaction Checker.`;
      checkerWarning.classList.remove('hidden');
    } else {
      checkerWarning.classList.add('hidden');
    }

    // Reset payment mode + method defaults
    document.querySelector('input[name="cb_payment_mode"][value="normal"]').checked = true;
    document.querySelector('input[name="cb_payment_method"][value="cash"]').checked = true;
    setSplitInput('cash', 0);
    setSplitInput('debit', computedGrandTotal);

    updatePaymentModeUI();
    updateSplitSummary();
    updateCloseBillingSubmitButton();

    document.getElementById('closeBillingModal').classList.remove('hidden');
  }

  function closeCloseBillingModal() {
    document.getElementById('closeBillingModal').classList.add('hidden');
    closeBillingBookingId = null;
    cbCurrentGrandTotal = 0;
    cbCheckerIncomplete = false;
    updateCloseBillingSubmitButton();
  }

  function updatePaymentModeUI() {
    const mode = document.querySelector('input[name="cb_payment_mode"]:checked')?.value || 'normal';
    const normalBlock = document.getElementById('cbNormalMethodBlock');
    const splitBlock = document.getElementById('cbSplitBlock');

    if (mode === 'split') {
      normalBlock.classList.add('hidden');
      splitBlock.classList.remove('hidden');
    } else {
      normalBlock.classList.remove('hidden');
      splitBlock.classList.add('hidden');
    }
  }

  function updateSplitSummary() {
    const fmt = v => formatRupiah(v || 0);
    const splitCash = Number(document.getElementById('cb_split_cash')?.value || 0);
    const splitDebit = Number(document.getElementById('cb_split_debit')?.value || 0);
    const splitTotal = splitCash + splitDebit;
    const diff = cbCurrentGrandTotal - splitTotal;

    document.getElementById('cbSplitTotal').textContent = fmt(splitTotal);
    document.getElementById('cbSplitDiff').textContent = fmt(diff);

    const summary = document.getElementById('cbSplitSummary');
    summary.classList.remove('border-red-200', 'bg-red-50', 'text-red-700');
    summary.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-700');

    if (Math.abs(diff) > 0.01) {
      summary.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-700');
      summary.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    }
  }

  async function submitCloseBilling() {
    if (!closeBillingBookingId) {
      return;
    }

    if (cbCheckerIncomplete) {
      alert('Billing tidak bisa ditutup karena masih ada item di Transaction Checker yang belum selesai.');
      return;
    }

    const paymentMode = document.querySelector('input[name="cb_payment_mode"]:checked')?.value;
    if (!paymentMode) {
      return;
    }

    const payload = {
      payment_mode: paymentMode,
    };

    if (paymentMode === 'normal') {
      const paymentMethod = document.querySelector('input[name="cb_payment_method"]:checked')?.value;
      if (!paymentMethod) {
        return;
      }
      payload.payment_method = paymentMethod;
    } else {
      const splitCashAmount = Number(document.getElementById('cb_split_cash').value || 0);
      const splitDebitAmount = Number(document.getElementById('cb_split_debit').value || 0);
      const splitTotal = splitCashAmount + splitDebitAmount;

      if (splitCashAmount <= 0 || splitDebitAmount <= 0) {
        alert('Untuk split bill, nominal cash dan debit harus lebih dari 0.');
        return;
      }

      if (Math.abs(splitTotal - cbCurrentGrandTotal) > 0.01) {
        alert('Total split (cash + debit) harus sama dengan grand total.');
        return;
      }

      payload.split_cash_amount = splitCashAmount;
      payload.split_debit_amount = splitDebitAmount;
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
        body: JSON.stringify(payload),
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

  document.querySelectorAll('input[name="cb_payment_mode"]').forEach((radio) => {
    radio.addEventListener('change', updatePaymentModeUI);
  });

  document.getElementById('cb_split_cash_display').addEventListener('input', (event) => onSplitInput('cash', event));
  document.getElementById('cb_split_debit_display').addEventListener('input', (event) => onSplitInput('debit', event));
</script>
