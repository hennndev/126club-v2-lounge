<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
  <title>Struk - {{ $order->order_number }}</title>
  <style>
    /* ── Reset ─────────────────────────────────────── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* ── Screen: centre the receipt on a grey bg ──── */
    body {
      background: #e5e7eb;
      font-family: 'Courier New', Courier, monospace;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 16px 40px;
      min-height: 100vh;
    }

    /* ── The receipt "paper" ─────────────────────── */
    .receipt {
      background: #fff;
      width: 302px;
      /* ≈ 80mm at 96 dpi */
      padding: 14px 12px 20px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
    }

    .receipt {
      font-size: 11px;
      color: #111;
      line-height: 1.4;
    }

    .receipt.walk-in-receipt,
    .receipt.walk-in-receipt * {
      font-weight: bold !important;
    }

    .store-name {
      font-size: 16px;
      font-weight: bold;
      letter-spacing: 1px;
      text-align: center;
      margin-bottom: 2px;
    }

    .store-sub {
      font-size: 10px;
      text-align: center;
      color: #444;
    }

    .section-sep {
      border: none;
      border-top: 1px dashed #777;
      margin: 8px 0;
    }

    /* ── Two-column info rows ────────────────────── */
    .row {
      display: flex;
      justify-content: space-between;
      margin: 1.5px 0;
    }

    .row .lbl {
      color: #555;
    }

    .row .val {
      font-weight: bold;
      text-align: right;
      max-width: 60%;
      word-break: break-all;
    }

    /* ── Items ───────────────────────────────────── */
    .item-block {
      margin: 4px 0;
    }

    .item-name {
      font-weight: bold;
      word-break: break-word;
    }

    .item-detail {
      display: flex;
      justify-content: space-between;
      color: #444;
      padding-left: 4px;
    }

    .item-detail .subtotal {
      font-weight: bold;
      color: #111;
    }

    /* ── Totals ──────────────────────────────────── */
    .total-row {
      display: flex;
      justify-content: space-between;
      margin: 2px 0;
    }

    .total-row.discount {
      color: #c00;
    }

    .grand-total-row {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      font-weight: bold;
      margin: 6px 0 2px;
      border-top: 1px solid #333;
      padding-top: 5px;
    }

    /* ── Footer ──────────────────────────────────── */
    .footer {
      text-align: center;
      margin-top: 10px;
    }

    .footer .thank-you {
      font-size: 11px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    .footer .note {
      font-size: 9.5px;
      color: #555;
      margin: 1.5px 0;
    }

    .footer .social {
      font-size: 10px;
      font-weight: bold;
      margin-top: 8px;
    }

    .footer .powered {
      font-size: 9px;
      color: #888;
      font-style: italic;
      margin-top: 6px;
    }

    /* ── Print button (screen only) ─────────────── */
    .print-btn {
      margin-top: 20px;
      padding: 10px 28px;
      background: #1e293b;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .print-btn:hover {
      background: #334155;
    }

    /* ── Print media ─────────────────────────────── */
    @page {
      size: 80mm auto;
      margin: 0mm;
    }

    @media print {

      html,
      body {
        width: 80mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        display: block !important;
      }

      .receipt {
        width: 80mm !important;
        padding: 4px 6px 12px !important;
        box-shadow: none !important;
      }

      .no-print {
        display: none !important;
      }
    }
  </style>
</head>

<body>

  <div class="receipt {{ $order->table_session_id === null ? 'walk-in-receipt' : '' }}">

    {{-- ── Store Header ── --}}
    <div class="store-name">126 CLUB</div>
    <div class="store-sub">Premium Nightclub &amp; Lounge</div>
    <div class="store-sub">Jl. Premium No. 126, Jakarta</div>
    <div class="store-sub">Telp: (021) 1234-5678</div>

    <hr class="section-sep"
        style="margin-top:10px;">

    {{-- ── Transaction Meta ── --}}
    <div class="row">
      <span class="lbl">No. Transaksi</span>
      <span class="val">{{ $order->order_number }}</span>
    </div>
    <div class="row">
      <span class="lbl">Tanggal</span>
      <span class="val">{{ ($order->ordered_at ?? now())->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
      <span class="lbl">Kasir</span>
      <span class="val">{{ auth()->user()?->name ?? 'Admin' }}</span>
    </div>

    <hr class="section-sep">

    {{-- ── Customer ── --}}
    <div class="row">
      <span class="lbl">Pelanggan</span>
      <span class="val">{{ $customerName }}</span>
    </div>
    <div class="row">
      <span class="lbl">Tipe</span>
      <span class="val">{{ $order->table_session_id === null ? 'WALK-IN' : 'BOOKING' }}</span>
    </div>

    @if ($order->table_session_id !== null)
      <div class="row">
        <span class="lbl">Meja</span>
        <span class="val">{{ $order->tableSession?->table?->table_number ?? '-' }}</span>
      </div>
    @endif

    <hr class="section-sep">

    {{-- ── Items ── --}}
    <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#555;margin-bottom:4px;">Item Pesanan</div>

    @foreach ($order->items as $item)
      <div class="item-block">
        <div class="item-name">{{ $item->item_name }}</div>
        <div class="item-detail">
          <span>{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
          <span class="subtotal">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
      </div>
    @endforeach

    <hr class="section-sep">

    {{-- ── Totals ── --}}
    @php
      $totals = $receiptTotals ?? [
          'items_total' => (float) $order->items_total,
          'discount_amount' => (float) $order->discount_amount,
          'service_charge_percentage' => 0,
          'service_charge' => 0,
          'tax_percentage' => 0,
          'tax' => 0,
          'grand_total' => (float) $order->total,
      ];
    @endphp

    @if (($totals['items_total'] ?? 0) > 0)
      <div class="total-row">
        <span>Subtotal</span>
        <span>Rp {{ number_format($totals['items_total'] ?? 0, 0, ',', '.') }}</span>
      </div>
    @endif

    @if (($totals['discount_amount'] ?? 0) > 0)
      <div class="total-row discount">
        <span>Diskon</span>
        <span>- Rp {{ number_format($totals['discount_amount'] ?? 0, 0, ',', '.') }}</span>
      </div>
    @endif

    @if (($totals['service_charge'] ?? 0) > 0)
      <div class="total-row">
        <span>Service Charge ({{ $totals['service_charge_percentage'] ?? 0 }}%)</span>
        <span>Rp {{ number_format($totals['service_charge'] ?? 0, 0, ',', '.') }}</span>
      </div>
    @endif

    @if (($totals['tax'] ?? 0) > 0)
      <div class="total-row">
        <span>PPN ({{ $totals['tax_percentage'] ?? 0 }}%)</span>
        <span>Rp {{ number_format($totals['tax'] ?? 0, 0, ',', '.') }}</span>
      </div>
    @endif

    <div class="grand-total-row">
      <span>TOTAL</span>
      <span>Rp {{ number_format($totals['grand_total'] ?? $order->total, 0, ',', '.') }}</span>
    </div>

    <hr class="section-sep">

    {{-- ── Footer ── --}}
    <div class="footer">
      <div class="thank-you">Terima Kasih Atas Kunjungan Anda!</div>
      <div class="note">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</div>
      <div class="note">Simpan struk ini sebagai bukti pembayaran yang sah</div>
      <div class="social">FOLLOW US</div>
      <div class="note"
           style="font-weight:bold;">&#64;126club &nbsp;|&nbsp; www.126club.com</div>
      <div class="powered">Powered by 126 Club POS System</div>
    </div>

  </div>

  {{-- ── Print Button (screen only) ── --}}
  <button class="print-btn no-print"
          onclick="window.print()">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="16"
         height="16"
         fill="none"
         viewBox="0 0 24 24"
         stroke="currentColor"
         stroke-width="2">
      <path stroke-linecap="round"
            stroke-linejoin="round"
            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
    </svg>
    Cetak Struk
  </button>

  <script>
    window.addEventListener('load', function() {
      setTimeout(function() {
        window.print();
      }, 400);
    });
  </script>

</body>

</html>
