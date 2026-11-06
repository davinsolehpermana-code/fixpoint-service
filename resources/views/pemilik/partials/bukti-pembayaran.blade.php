<style>
    .fp-qris-owner-section { margin:0 0 20px; }
    .fp-qris-owner-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; }
    .fp-qris-owner-item { min-width:0; display:grid; grid-template-columns:92px minmax(0,1fr); gap:12px; align-items:center; padding:11px; border:1px solid #e5eaf5; border-radius:12px; background:#fff; }
    .fp-qris-owner-image { display:block; width:92px; height:74px; border:1px solid #dbeafe; border-radius:9px; object-fit:cover; background:#f1f5f9; }
    .fp-qris-owner-id { color:#1d4ed8; font-size:12px; font-weight:850; overflow-wrap:anywhere; }
    .fp-qris-owner-meta { color:#64748b; font-size:11px; line-height:1.55; margin-top:4px; }
    .fp-qris-owner-status { display:inline-flex; margin-top:7px; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:800; }
    .fp-qris-owner-waiting { color:#1d4ed8; background:#dbeafe; }
    .fp-qris-owner-paid { color:#166534; background:#dcfce7; }
    .fp-qris-owner-unpaid { color:#92400e; background:#fef3c7; }
    .fp-qris-owner-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:11px; color:#64748b; background:#f8fafc; font-size:12px; text-align:center; }
    @media(max-width:1000px){ .fp-qris-owner-grid{grid-template-columns:repeat(2,minmax(0,1fr));} }
    @media(max-width:650px){ .fp-qris-owner-grid{grid-template-columns:1fr;} }
</style>
<section class="fp-qris-owner-section">
    <div class="card">
        <div class="card-header" style="align-items:flex-start;">
            <div>
                <div class="owner-label" style="margin-bottom:4px;">Monitoring Transaksi</div>
                <h2 class="card-title">Bukti Pembayaran QRIS</h2>
                <p class="card-description">Pemilik dapat melihat bukti dan status pembayaran. Tidak tersedia tindakan verifikasi.</p>
            </div>
            <span class="status status-process">Hanya Lihat</span>
        </div>

        @if(($paymentProofs ?? collect())->isNotEmpty())
            <div class="fp-qris-owner-grid">
                @foreach($paymentProofs as $proof)
                    @php
                        $statusText = match($proof->payment_status) {
                            'PAID' => 'Lunas',
                            'WAITING_VERIFICATION' => 'Menunggu Verifikasi',
                            default => 'Perlu Upload Ulang',
                        };
                        $statusClass = match($proof->payment_status) {
                            'PAID' => 'fp-qris-owner-paid',
                            'WAITING_VERIFICATION' => 'fp-qris-owner-waiting',
                            default => 'fp-qris-owner-unpaid',
                        };
                    @endphp
                    <article class="fp-qris-owner-item">
                        <a href="{{ route('pemilik.payments.proof', $proof->payment_id) }}" target="_blank" rel="noopener">
                            <img class="fp-qris-owner-image" src="{{ route('pemilik.payments.proof', $proof->payment_id) }}" alt="Bukti QRIS {{ $proof->service_id_code }}" loading="lazy">
                        </a>
                        <div>
                            <div class="fp-qris-owner-id">{{ $proof->service_id_code }}</div>
                            <div class="fp-qris-owner-meta">
                                {{ $proof->customer_name ?? 'Pelanggan' }}<br>
                                Rp {{ number_format((float)$proof->amount, 0, ',', '.') }}
                            </div>
                            <span class="fp-qris-owner-status {{ $statusClass }}">{{ $statusText }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="fp-qris-owner-empty">Belum ada bukti pembayaran QRIS yang dapat ditampilkan.</div>
        @endif
    </div>
</section>
