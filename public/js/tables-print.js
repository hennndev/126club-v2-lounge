function printQRCode() {
    const printWindow = window.open('', '', 'width=800,height=600');
    const table = currentQRTable;

    if (!table) return;

    const qrCanvas = document.querySelector('#qrcodeContainer canvas');
    const qrImage = qrCanvas ? qrCanvas.toDataURL() : '';

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QR Code - ${table.table_number}</title>
            <style>
                @media print {
                    @page {
                        margin: 0;
                        size: 10cm 20cm;
                    }
                    body { margin: 0.5cm; }
                }
                body {
                    font-family: system-ui, -apple-system, sans-serif;
                    text-align: center;
                    padding: 15px;
                    background: white;
                }
                .label-container {
                    border: 2px solid #1e293b;
                    border-radius: 8px;
                    padding: 15px;
                    max-width: 10cm;
                    margin: 0 auto;
                }
                .header {
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #e2e8f0;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: #1e293b;
                    margin-bottom: 5px;
                }
                .subtitle {
                    font-size: 12px;
                    color: #64748b;
                }
                .table-name {
                    font-size: 22px;
                    font-weight: bold;
                    margin: 10px 0;
                    color: #1e293b;
                }
                .area-name {
                    font-size: 14px;
                    color: #64748b;
                    margin-bottom: 15px;
                }
                .qr-container {
                    margin: 14px auto;
                    padding: 10px;
                    border: 2px solid #1e293b;
                    border-radius: 8px;
                    display: inline-block;
                    background: white;
                }
                .qr-container img {
                    display: block;
                }
                .info {
                    margin-top: 15px;
                    padding: 12px;
                    background: #f1f5f9;
                    border-radius: 6px;
                    text-align: left;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 8px 0;
                }
                .info-label {
                    color: #64748b;
                    font-size: 13px;
                }
                .info-value {
                    font-weight: bold;
                    font-size: 13px;
                    color: #1e293b;
                }
                .qr-code-text {
                    font-family: monospace;
                    font-size: 10px;
                    color: #94a3b8;
                    margin-top: 12px;
                    letter-spacing: 0.5px;
                }
                .footer {
                    margin-top: 15px;
                    font-size: 11px;
                    color: #94a3b8;
                    padding-top: 10px;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class="label-container">
                <div class="header">
                    <div class="logo">🏢 126 Club</div>
                    <div class="subtitle">Premium Management</div>
                </div>

                <div class="table-name">${table.table_number}</div>
                <div class="area-name">${table.area.name}</div>

                <div class="qr-container">
                    <img src="${qrImage}" alt="QR Code" width="150" height="150">
                </div>

                <div class="info">
                    <div class="info-row">
                        <span class="info-label">Kapasitas:</span>
                        <span class="info-value">${table.capacity} orang</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Minimum Charge:</span>
                        <span class="info-value">Rp ${new Intl.NumberFormat('id-ID').format(table.minimum_charge)}jt</span>
                    </div>
                </div>

                <div class="qr-code-text">${table.qr_code}</div>

                <div class="footer">
                    Scan QR code untuk informasi & reservasi
                </div>
            </div>

            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        };
                    }, 250);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
