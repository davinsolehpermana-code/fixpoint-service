<style>
    .fp-qris-admin-section { margin:0 0 18px; }
    .fp-qris-admin-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; }
    .fp-qris-admin-item { min-width:0; display:grid; grid-template-columns:92px minmax(0,1fr); gap:12px; align-items:center; padding:11px; border:1px solid #e7edf7; border-radius:12px; background:#fbfdff; }
    .fp-qris-admin-image { display:block; width:92px; height:74px; border:1px solid #dbeafe; border-radius:9px; object-fit:cover; background:#f1f5f9; }
    .fp-qris-admin-id { color:#1d4ed8; font-size:12px; font-weight:850; overflow-wrap:anywhere; }
    .fp-qris-admin-meta { color:#64748b; font-size:11px; line-height:1.55; margin-top:4px; }
    .fp-qris-admin-status { display:inline-flex; margin-top:7px; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:800; }
    .fp-qris-admin-waiting { color:#1d4ed8; background:#dbeafe; }
    .fp-qris-admin-paid { color:#166534; background:#dcfce7; }
    .fp-qris-admin-unpaid { color:#92400e; background:#fef3c7; }
    .fp-qris-admin-empty { padding:19px; border:1px dashed #cbd5e1; border-radius:11px; color:#64748b; background:#f8fafc; font-size:12px; text-align:center; }
    @media(max-width:1000px){ .fp-qris-admin-grid{grid-template-columns:repeat(2,minmax(0,1fr));} }
    @media(max-width:650px){ .fp-qris-admin-grid{grid-template-columns:1fr;} }
</style>
<section class="admin-card fp-qris-admin-section">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Bukti Pembayaran QRIS</h2>
            <p class="admin-card-description">Bukti terbaru dari pelanggan yang perlu dipantau Admin.</p>
        </div>
        <a href="{{ route('admin.payments') }}" class="admin-btn admin-btn-soft">Buka Pembayaran</a>
    </div>

    @if(($proofs ?? collect())->isNotEmpty())
        <div class="fp-qris-admin-grid">
            @foreach($proofs as $proof)
                @php
                    $statusText = match($proof->payment_status) {
                        'PAID' => 'Lunas',
                        'WAITING_VERIFICATION' => 'Menunggu Verifikasi',
                        default => 'Perlu Upload Ulang',
                    };
                    $statusClass = match($proof->payment_status) {
                        'PAID' => 'fp-qris-admin-paid',
                        'WAITING_VERIFICATION' => 'fp-qris-admin-waiting',
                        default => 'fp-qris-admin-unpaid',
                    };
                @endphp
                <article class="fp-qris-admin-item">
                    <a href="{{ route('admin.payments.proof', $proof->payment_id) }}" target="_blank" rel="noopener">
                        <img class="fp-qris-admin-image" src="{{ route('admin.payments.proof', $proof->payment_id) }}" alt="Bukti QRIS {{ $proof->service_id_code }}" loading="lazy">
                    </a>
                    <div>
                        <div class="fp-qris-admin-id">{{ $proof->service_id_code }}</div>
                        <div class="fp-qris-admin-meta">
                            {{ $proof->customer_name ?? 'Pelanggan' }}<br>
                            Rp {{ number_format((float)$proof->amount, 0, ',', '.') }}
                        </div>
                        <span class="fp-qris-admin-status {{ $statusClass }}">{{ $statusText }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="fp-qris-admin-empty">Belum ada bukti pembayaran QRIS yang diunggah.</div>
    @endif
</section>
