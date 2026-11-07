<style>
    .admin-cancel-overlay { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.56); display:none; align-items:center; justify-content:center; padding:18px; }
    .admin-cancel-overlay:not([hidden]) { display:flex; }
    .admin-cancel-panel { width:min(100%,440px); background:#fff; border-radius:17px; padding:23px; box-shadow:0 25px 70px rgba(15,23,42,.25); }
    .admin-cancel-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .admin-cancel-title { font-family:var(--font-display); font-size:18px; font-weight:800; margin:0; color:#0f172a; }
    .admin-cancel-close { border:0; background:#f1f5f9; border-radius:8px; width:32px; height:32px; font-size:19px; cursor:pointer; }
    .admin-cancel-message { font-family:var(--font-sans); font-size:13px; line-height:1.6; color:#64748b; margin:0 0 14px; }
    .admin-cancel-summary { background:#f8fafc; border:1px solid #eef2f7; border-radius:11px; padding:11px 12px; margin-bottom:14px; font-family:var(--font-sans); font-size:12px; color:#334155; line-height:1.7; }
    .admin-cancel-summary strong { font-family:var(--font-mono); color:#2563eb; }
    .admin-cancel-label { display:block; font-family:var(--font-sans); font-size:11px; font-weight:750; color:#475569; margin-bottom:6px; }
    .admin-cancel-textarea { width:100%; min-height:90px; resize:vertical; border:1px solid #d8dee8; border-radius:9px; background:#fff; color:#0f172a; padding:10px 12px; font-family:var(--font-sans); font-size:12px; outline:none; }
    .admin-cancel-textarea:focus { border-color:#60a5fa; box-shadow:0 0 0 3px rgba(59,130,246,.10); }
    .admin-cancel-actions { display:flex; justify-content:flex-end; gap:9px; margin-top:18px; }
    body.admin-cancel-open { overflow:hidden; }
</style>

<div class="admin-cancel-overlay" id="adminCancelModal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="adminCancelTitle">
    <form method="POST" id="adminCancelForm" class="admin-cancel-panel" action="#">
        @csrf
        <div class="admin-cancel-head">
            <h2 class="admin-cancel-title" id="adminCancelTitle">Batalkan Booking</h2>
            <button type="button" class="admin-cancel-close" id="adminCancelClose" aria-label="Tutup">&times;</button>
        </div>
        <p class="admin-cancel-message">
            Booking akan dibatalkan dan slot jadwal dikembalikan. Tindakan ini tidak dapat diurungkan.
        </p>
        <div class="admin-cancel-summary">
            Service ID: <strong id="adminCancelCode">—</strong><br>
            Pelanggan: <span id="adminCancelCustomer">—</span>
        </div>
        <label class="admin-cancel-label" for="adminCancelReason">Alasan Pembatalan</label>
        <textarea name="cancellation_reason" id="adminCancelReason" class="admin-cancel-textarea" maxlength="500"
            placeholder="Tuliskan alasan pembatalan dari sisi Admin..." required></textarea>
        <div class="admin-cancel-actions">
            <button type="button" class="admin-btn admin-btn-outline" id="adminCancelDismiss">Batal</button>
            <button type="submit" class="admin-btn admin-btn-danger">Ya, Batalkan Booking</button>
        </div>
    </form>
</div>

<script>
(() => {
    const modal = document.getElementById('adminCancelModal');
    const form = document.getElementById('adminCancelForm');
    const code = document.getElementById('adminCancelCode');
    const customer = document.getElementById('adminCancelCustomer');
    const reason = document.getElementById('adminCancelReason');
    let previousFocus = null;

    if (!modal || !form) return;

    function openCancelModal(button) {
        previousFocus = document.activeElement;
        form.action = button.dataset.cancelUrl || '#';
        code.textContent = button.dataset.code || '—';
        customer.textContent = button.dataset.customer || '—';
        reason.value = '';
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('admin-cancel-open');
        reason.focus();
    }

    function closeCancelModal() {
        if (modal.hidden) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('admin-cancel-open');
        previousFocus?.focus?.();
    }

    document.querySelectorAll('.js-cancel-booking').forEach((button) => {
        button.addEventListener('click', () => openCancelModal(button));
    });

    document.getElementById('adminCancelClose')?.addEventListener('click', closeCancelModal);
    document.getElementById('adminCancelDismiss')?.addEventListener('click', closeCancelModal);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeCancelModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeCancelModal(); });
})();
</script>
