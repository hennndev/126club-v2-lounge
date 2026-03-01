<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
  <title>Struk - {{ $billing?->transaction_code ?? 'N/A' }}</title>
  <style>
    @page {
      margin: 0;
      size: 80mm auto;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Courier New', Courier, monospace;
      font-size: 12px;
      color: #111;
      background: #fff;
      width: 80mm;
      margin: 0 auto;
      padding: 16px 12px 24px;
    }

    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    .bold {
      font-weight: bold;
    }

    .sep {
      border: none;
      border-top: 1px dashed #555;
      margin: 10px 0;
    }

    .two-col {
      display: flex;
      justify-content: space-between;
      margin: 2px 0;
    }

    .two-col .label {
      color: #333;
    }

    .two-col .value {
      font-weight: bold;
      text-align: right;
    }

    table.items {
      width: 100%;
      border-collapse: collapse;
      margin: 4px 0;
    }

    table.items thead th {
      text-align: left;
      font-size: 11px;
      font-weight: bold;
      padding: 2px 0;
      border-bottom: 1px solid #333;
    }

    table.items thead th.right {
      text-align: right;
    }

    table.items tbody td {
      padding: 4px 0 2px;
      vertical-align: top;
    }

    table.items tbody td.right {
      text-align: right;
      white-space: nowrap;
    }

    .item-name {
      font-weight: bold;
      font-size: 11px;
    }

    .item-cat {
      font-size: 10px;
      color: #555;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      margin: 2px 0;
      font-size: 11px;
    }

    .grand-total {
      font-size: 14px;
      font-weight: bold;
      margin: 6px 0 2px;
    }

    .footer-text {
      font-size: 10px;
      color: #444;
      margin: 2px 0;
    }

    .print-btn {
      display: block;
      margin: 20px auto 0;
      padding: 8px 24px;
      background: #1e293b;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      cursor: pointer;
    }

    @media print {
      .no-print {
        display: none !important;
      }

      body {
        padding: 8px 8px 16px;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
  <div class="center">
    <div style="font-size:18px;font-weight:bold;letter-spacing:1px;">126 CLUB</div>
    <div style="font-size:11px;margin-top:2px;">Premium Nightclub &amp; Lounge</div>
    <div style="font-size:10px;color:#444;margin-top:2px;">Jl. Premium No. 126, Jakarta</div>
    <div style="font-size:10px;color:#444;">Telp: (021) 1234-5678</div>
  </div>

  <hr class="sep"
      style="margin-top:12px;">

  <!-- Transaction Info -->
  <div class="two-col">
    <span class="label">No. Transaksi</span>
    <span class="value">{{ $billing?->transaction_code ?? '-' }}</span>
  </div>
  <div class="two-col">
    <span class="label">Tanggal</span>
    <span class="value">{{ $billing?->updated_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</span>
  </div>
  <div class="two-col">
    <span class="label">Kasir</span>
    <span class="value">{{ auth()->user()?->name ?? 'Admin' }}</span>
  </div>

  <hr class="sep">

  <!-- Customer Info -->
  <div class="two-col">
    <span class="label">Pelanggan</span>
    <span class="value">{{ $customerName }}</span>
  </div>
  <div class="two-col">
    <span class="label">Tipe</span>
    <span class="value">BOOKING</span>
  </div>
  <div class="two-col">
    <span class="label">Meja</span>
    <span class="value">{{ $booking->table?->table_number ?? '-' }}</span>
  </div>

  <hr class="sep">

  <!-- Items -->
  <table class="items">
    <thead>
      <tr>
        <th>Item</th>
        <th class="right">Qty</th>
        <th class="right">Harga</th>
        <th class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($allItems as $item)
        <tr>
          <td>
            <div class="item-name">{{ $item['name'] }}</div>
            <div class="item-cat">Beverage</div>
          </td>
          <td class="right">{{ $item['qty'] }}</td>
          <td class="right">{{ number_format($item['price'], 0, ',', '.') }}</td>
          <td class="right">{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <hr class="sep">

  <!-- Totals -->
  @if (($billing?->minimum_charge ?? 0) > 0)
    <div class="total-row">
      <span>Minimum Charge</span>
      <span>Rp {{ number_format($billing->minimum_charge, 0, ',', '.') }}</span>
    </div>
  @endif
  <div class="total-row">
    <span>Subtotal</span>
    <span>Rp {{ number_format($billing?->subtotal ?? 0, 0, ',', '.') }}</span>
  </div>
  @if (($billing?->discount_amount ?? 0) > 0)
    <div class="total-row">
      <span>Diskon</span>
      <span>- Rp {{ number_format($billing->discount_amount, 0, ',', '.') }}</span>
    </div>
  @endif

  <div class="two-col grand-total">
    <span>TOTAL</span>
    <span>Rp {{ number_format($billing?->grand_total ?? 0, 0, ',', '.') }}</span>
  </div>

  <hr class="sep">

  <div class="two-col">
    <span class="label">Metode Pembayaran</span>
    <span class="value">{{ strtoupper($billing?->payment_method ?? '-') }}</span>
  </div>

  <hr class="sep">

  <!-- Footer -->
  <div class="center"
       style="margin-top:6px;">
    <div class="bold"
         style="font-size:12px;">Terima Kasih Atas Kunjungan<br>Anda!</div>
    <div class="footer-text"
         style="margin-top:6px;">Barang yang sudah dibeli tidak dapat<br>ditukar/dikembalikan</div>
    <div class="footer-text"
         style="margin-top:4px;">Simpan struk ini sebagai bukti<br>pembayaran yang sah</div>
    <div class="bold"
         style="margin-top:10px;font-size:11px;">FOLLOW US</div>
    <div class="footer-text">@126club | www.126club.com</div>
    <div style="font-size:10px;font-style:italic;color:#777;margin-top:8px;">Powered by 126 Club POS System</div>
  </div>

  <!-- Print button (hidden when printing) -->
  <button class="print-btn no-print"
          onclick="window.print()">&#128438; Cetak Struk</button>
  <script>
    // Auto-print when opened in a new tab
    window.addEventListener('load', function() {
      if (window.opener || document.referrer.includes('/bookings')) {
        setTimeout(function() {
          window.print();
        }, 300);
      }
    });
  </script>
</body>

</html>
