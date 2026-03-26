<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
  <h2 style="margin: 0 0 12px;">Permintaan OTP</h2>
  <p style="margin: 0 0 8px;">Daily OTP diminta dari sistem POS.</p>
  <p style="margin: 0 0 8px;"><strong>Requested By:</strong> {{ $requestedBy }}</p>
  <p style="margin: 0 0 16px;"><strong>Requested At:</strong> {{ $requestedAt }}</p>

  <div style="display: inline-block; padding: 10px 14px; border-radius: 8px; background: #f1f5f9; border: 1px solid #cbd5e1;">
    <span style="font-size: 24px; letter-spacing: 6px; font-weight: 700;">{{ $code }}</span>
  </div>

  <p style="margin: 16px 0 0; font-size: 12px; color: #64748b;">Jangan bagikan kode ini ke pihak yang tidak berwenang.</p>
</div>
