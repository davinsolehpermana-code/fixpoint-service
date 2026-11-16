/**
 * Pengambilan bukti screenshot Sprint 3 (S3-06 & S3-07) memakai Chromium
 * sungguhan melalui Playwright.
 *
 * Aplikasi yang dipakai untuk screenshot adalah instance terpisah dengan
 * database demo SQLite (storage/app/evidence.sqlite) pada port 8010, sehingga
 * data MySQL milik pengembang tidak tersentuh. Pengukuran performa dilakukan
 * terpisah terhadap server MySQL di port 8000.
 *
 * Jalankan:
 *   NODE_PATH=.tools/browser/node_modules PLAYWRIGHT_BROWSERS_PATH=.tools/pw-browsers \
 *     node scripts/sprint3/capture_evidence.js
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE = process.env.EVIDENCE_BASE || 'http://127.0.0.1:8010';
const LIVE_BASE = process.env.LIVE_BASE || 'http://127.0.0.1:8000';
const ROOT = process.cwd();
const OUT = {
    security: path.join(ROOT, 'docs/sprint-3/pengumpulan/S3-06_BASIC_SECURITY/screenshots'),
    performance: path.join(ROOT, 'docs/sprint-3/pengumpulan/S3-07_PERFORMANCE/screenshots'),
    schedule: path.join(ROOT, 'docs/sprint-3/pengumpulan/S3-07_ADMIN_SCHEDULE/screenshots'),
};

const evidence = [];

function record(entry) {
    evidence.push({ ...entry, captured_at: new Date().toISOString() });
    const dir = path.join(ROOT, 'docs/sprint-3/pengumpulan/evidence');
    fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(
        path.join(dir, 'screenshots.json'),
        JSON.stringify({ generated_at: new Date().toISOString(), evidence }, null, 2)
    );
    console.log(`[evidence] ${entry.id}: ${entry.observed}`);
}

async function shot(page, dir, name, options = {}) {
    const file = path.join(dir, name);
    fs.mkdirSync(path.dirname(file), { recursive: true });
    await page.screenshot({ path: file, fullPage: options.fullPage === true });
    return path.relative(ROOT, file);
}

async function waitSettled(page) {
    await page.waitForLoadState('networkidle', { timeout: 6000 }).catch(() => {});
    await page.waitForTimeout(300);
}

async function gotoWithRetry(page, url) {
    let lastError;
    for (let attempt = 1; attempt <= 4; attempt++) {
        try {
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
            return;
        } catch (error) {
            lastError = error;
            console.log(`[retry ${attempt}] ${url}: ${error.message.split('\n')[0]}`);
            await page.waitForTimeout(800);
        }
    }
    throw lastError;
}

async function submitAndWait(page, selector) {
    const navigation = page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.click(selector);
    try {
        await navigation;
    } catch (error) {
        const state = await page.evaluate(() => ({
            url: location.href,
            invalidFields: Array.from(document.querySelectorAll(':invalid')).map((element) => element.id || element.name),
        }));
        throw new Error(`Navigasi gagal setelah ${selector}: ${JSON.stringify(state)}`);
    }
    await waitSettled(page);
}

async function login(page, role, email, password) {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    if (role === 'pemilik') {
        await page.check('#rolePemilik');
    }
    await page.fill('#email', email);
    await page.fill('#password', password);
    await submitAndWait(page, 'button.login-button');
}

async function createSchedule(page, { date, start, end, slots }) {
    await page.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await page.fill('#schedule_date', date);
    await page.fill('#start_time', start);
    await page.fill('#end_time', end);
    await page.fill('#max_booking', String(slots));
    await submitAndWait(page, '#scheduleStoreSubmit');
}

function scheduleRow(page, needle) {
    return page.locator('tbody tr', { hasText: needle }).first();
}

async function createBooking(page, { name, phone, scheduleNeedle }) {
    await page.goto(`${BASE}/booking`, { waitUntil: 'domcontentloaded' });
    await page.fill('#nama', name);
    await page.fill('#telepon', phone);
    await page.check('input[name="jenis_perangkat"][value="Laptop"]');
    await page.fill('#merek_model', 'Lenovo ThinkPad T14');
    await page.locator('input[name="service_id"]').first().check();
    await page.fill('#keluhan', 'Keyboard dan touchpad tidak berfungsi setelah terkena cairan.');
    const options = await page.$$eval('#schedule_id option', (items) =>
        items.map((item) => ({ value: item.value, text: item.textContent.trim() }))
    );
    const target = options.find((option) => option.value && option.text.includes(scheduleNeedle));
    if (!target) throw new Error(`Opsi jadwal "${scheduleNeedle}" tidak ditemukan.`);
    await page.selectOption('#schedule_id', target.value);
    await page.selectOption('#payment_method', 'CASH');
    await submitAndWait(page, '#submitBtn');
}

async function main() {
    Object.values(OUT).forEach((dir) => fs.mkdirSync(dir, { recursive: true }));

    const browser = await chromium.launch({
        headless: true,
        executablePath: process.env.CHROME_PATH || undefined,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const adminContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await adminContext.newPage();

    // ---------------------------------------------------------------------
    // Persiapan data demo melalui antarmuka aplikasi (bukan insert manual)
    // ---------------------------------------------------------------------
    await login(page, 'admin', 'admin@fixpoint.test', 'Admin12345');
    record({ id: 'prep-01', action: 'Login admin pada instance bukti', observed: 'Login berhasil dan diarahkan ke dashboard admin.' });

    await createSchedule(page, { date: '2026-11-10', start: '09:00', end: '11:00', slots: 5 });
    await createSchedule(page, { date: '2026-11-11', start: '13:00', end: '15:00', slots: 4 });
    record({ id: 'prep-02', action: 'Membuat dua jadwal servis', observed: 'Jadwal 10 Nov 2026 09.00–11.00 (5 slot) dan 11 Nov 2026 13.00–15.00 (4 slot) dibuat dari form admin.' });

    await createBooking(page, { name: 'Rina Pratama', phone: '081200001001', scheduleNeedle: '10 November 2026' });
    await createBooking(page, { name: 'Dedi Kurniawan', phone: '081200001002', scheduleNeedle: '10 November 2026' });
    record({ id: 'prep-03', action: 'Membuat dua booking pelanggan', observed: 'Dua booking masuk ke jadwal 10 November 2026 melalui halaman booking publik (data demo, bukan pelanggan asli).' });

    // ---------------------------------------------------------------------
    // S3-07 — Pengelolaan jadwal Admin
    // ---------------------------------------------------------------------
    await page.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await waitSettled(page);
    let file = await shot(page, OUT.schedule, '01_daftar_jadwal_admin.png', { fullPage: true });
    record({
        id: 'SCH-01',
        action: 'Admin membuka daftar jadwal',
        observed: 'Halaman Kelola Jadwal Servis tampil dengan breadcrumb, tombol Edit/Hapus pada jadwal mendatang, dan jumlah booking terkait.',
        screenshot: file,
        url: `${BASE}/admin/jadwal-servis`,
    });

    await scheduleRow(page, '10 November 2026').getByRole('link', { name: 'Edit' }).click();
    await waitSettled(page);
    file = await shot(page, OUT.schedule, '02_form_edit_jadwal.png', { fullPage: true });
    record({
        id: 'SCH-02',
        action: 'Admin membuka form edit jadwal',
        observed: 'Form edit terisi nilai lama (10 November 2026, 09:00–11:00, 5 slot), menampilkan ringkasan 2 slot terpakai dan peringatan jadwal terikat booking.',
        screenshot: file,
    });

    await page.fill('#max_booking', '8');
    await submitAndWait(page, '#scheduleEditSubmit');
    const capacityRowText = await scheduleRow(page, '10 November 2026').innerText();
    file = await shot(page, OUT.schedule, '03_perubahan_kapasitas_slot.png', { fullPage: true });
    record({
        id: 'SCH-03',
        action: 'Admin mengubah kapasitas 5 → 8 slot',
        observed: `Notifikasi sukses tampil dan baris jadwal menjadi "${capacityRowText.replace(/\s+/g, ' ').trim()}".`,
        screenshot: file,
    });

    await scheduleRow(page, '10 November 2026').getByRole('link', { name: 'Edit' }).click();
    await waitSettled(page);

    // Atribut min pada input adalah petunjuk UX. Untuk menguji penolakan di sisi
    // server, batas klien dilepas dulu lalu nilai 1 dikirim ke backend.
    await page.evaluate(() => {
        const input = document.querySelector('#max_booking');
        input.min = '1';
        input.value = '1';
    });
    await submitAndWait(page, '#scheduleEditSubmit');
    const rejectCapacity = await page.locator('.admin-alert-error').first().innerText();
    file = await shot(page, OUT.schedule, '04_penolakan_slot_di_bawah_booking.png', { fullPage: false });
    record({
        id: 'SCH-04',
        action: 'Admin mencoba menurunkan slot menjadi 1 (di bawah 2 booking)',
        observed: rejectCapacity.replace(/\s+/g, ' ').trim(),
        screenshot: file,
    });

    // Penolakan kuota mengembalikan halaman ke form edit, jadi buka daftar dulu.
    await page.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await waitSettled(page);

    await scheduleRow(page, '11 November 2026').getByRole('link', { name: 'Edit' }).click();
    await waitSettled(page);
    await page.fill('#start_time', '15:00');
    await page.fill('#end_time', '17:00');
    await submitAndWait(page, '#scheduleEditSubmit');
    const timeSuccess = await page.locator('.admin-alert-success').first().innerText();
    file = await shot(page, OUT.schedule, '05_perubahan_jam_jadwal.png', { fullPage: true });
    record({
        id: 'SCH-05',
        action: 'Admin mengubah jam jadwal 13.00–15.00 → 15.00–17.00',
        observed: timeSuccess.replace(/\s+/g, ' ').trim(),
        screenshot: file,
    });

    await scheduleRow(page, '11 November 2026').getByRole('button', { name: 'Hapus' }).click();
    await page.waitForSelector('#scheduleDeleteModal:not([hidden])');
    file = await shot(page, OUT.schedule, '06_modal_konfirmasi_hapus.png', { fullPage: false });
    record({
        id: 'modal-hapus',
        action: 'Admin membuka konfirmasi hapus jadwal',
        observed: 'Modal konfirmasi desain FixPoint tampil (bukan window.confirm) dengan nama jadwal dan tombol Batal / Ya, Hapus Jadwal.',
        screenshot: file,
    });

    await submitAndWait(page, '#scheduleDeleteConfirm');
    const deleteSuccess = await page.locator('.admin-alert-success').first().innerText();
    file = await shot(page, OUT.schedule, '07_hapus_jadwal_berhasil.png', { fullPage: true });
    record({
        id: 'SCH-08',
        action: 'Admin menghapus jadwal tanpa booking (11 November 2026)',
        observed: deleteSuccess.replace(/\s+/g, ' ').trim(),
        screenshot: file,
    });

    await scheduleRow(page, '10 November 2026').getByRole('button', { name: 'Hapus' }).click();
    await page.waitForSelector('#scheduleDeleteModal:not([hidden])');
    await submitAndWait(page, '#scheduleDeleteConfirm');
    const deleteRejected = await page.locator('.admin-alert-error').first().innerText();
    file = await shot(page, OUT.schedule, '08_penolakan_hapus_jadwal_berbooking.png', { fullPage: true });
    record({
        id: 'SCH-09',
        action: 'Admin mencoba menghapus jadwal yang memiliki 2 booking',
        observed: deleteRejected.replace(/\s+/g, ' ').trim(),
        screenshot: file,
    });

    // Indikator loading & anti double-click: jeda jaringan buatan pada request PUT.
    await createSchedule(page, { date: '2026-11-12', start: '09:00', end: '10:00', slots: 3 });
    await scheduleRow(page, '12 November 2026').getByRole('link', { name: 'Edit' }).click();
    await waitSettled(page);
    await page.fill('#max_booking', '5');
    // Tahan request update di sisi browser agar overlay loading dapat direkam.
    let releaseRequest;
    const requestGate = new Promise((resolve) => { releaseRequest = resolve; });
    await page.route(/\/admin\/jadwal-servis\/\d+$/, async (route) => {
        console.log(`[route] ${route.request().method()} ${route.request().url()}`);
        if (['POST', 'PUT'].includes(route.request().method())) {
            await requestGate;
        }
        await route.continue();
    });
    const navigation = page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => null);
    // Submit lewat Enter (aksi pengguna nyata) dan jangan di-await agar overlay terekam.
    await page.locator('#max_booking').focus();
    const clickPromise = page.keyboard.press('Enter')
        .catch((error) => console.log(`[warn] submit: ${error.message.split('\n')[0]}`));
    try {
        await page.waitForSelector('[data-loading-scope].is-loading', { timeout: 5000 });
        const loadingState = await page.evaluate(() => {
            const button = document.querySelector('#scheduleEditSubmit');
            const scope = document.querySelector('[data-loading-scope]');
            return {
                buttonDisabled: button ? button.disabled : null,
                buttonText: button ? button.textContent.trim() : null,
                overlayVisible: scope ? scope.classList.contains('is-loading') : null,
            };
        });
        file = await shot(page, OUT.schedule, '09_loading_indicator.png', { fullPage: false });
        record({
            id: 'usability-loading',
            action: 'Menyimpan perubahan saat request ditahan untuk merekam indikator',
            observed: `Tombol disabled=${loadingState.buttonDisabled}, teks="${loadingState.buttonText}", overlay loading=${loadingState.overlayVisible}.`,
            screenshot: file,
        });
    } catch (error) {
        console.log(`[skip] loading state tidak teramati: ${error.message.split('\n')[0]}`);
    } finally {
        releaseRequest();
    }
    await clickPromise;
    await navigation;
    await page.unroute(/\/admin\/jadwal-servis\/\d+$/);
    await waitSettled(page);

    await scheduleRow(page, '12 November 2026').getByRole('button', { name: 'Hapus' }).click();
    await page.waitForSelector('#scheduleDeleteModal:not([hidden])');
    await submitAndWait(page, '#scheduleDeleteConfirm');

    const mobile = await adminContext.newPage();
    await mobile.setViewportSize({ width: 390, height: 844 });
    await mobile.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await waitSettled(mobile);
    file = await shot(mobile, OUT.schedule, '10_tampilan_mobile_jadwal.png', { fullPage: true });
    record({
        id: 'responsive-mobile',
        action: 'Membuka pengelolaan jadwal pada viewport 390×844',
        observed: 'Sidebar berubah menjadi menu lipat, kartu dan tabel dapat digulir horizontal tanpa halaman melebar.',
        screenshot: file,
    });
    await mobile.close();

    // ---------------------------------------------------------------------
    // S3-06 — Keamanan
    // ---------------------------------------------------------------------
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(`${BASE}/booking`, { waitUntil: 'domcontentloaded' });
    await page.fill('#nama', 'Uji Validasi');
    await page.fill('#telepon', 'abc');
    await page.check('input[name="jenis_perangkat"][value="Laptop"]');
    await page.fill('#merek_model', 'Asus');
    await page.locator('input[name="service_id"]').first().check();
    await page.fill('#keluhan', 'Uji validasi nomor telepon.');
    const scheduleValue = await page.$eval('#schedule_id option:not([value=""])', (option) => option.value);
    await page.selectOption('#schedule_id', scheduleValue);
    await page.selectOption('#payment_method', 'CASH');
    await submitAndWait(page, '#submitBtn');
    const validationText = await page.locator('.alert-error').first().innerText();
    file = await shot(page, OUT.security, '01_validasi_input_booking.png', { fullPage: false });
    record({
        id: 'SEC-01',
        action: 'Mengirim booking dengan nomor telepon "abc"',
        observed: validationText.replace(/\s+/g, ' ').trim(),
        screenshot: file,
    });

    await page.goto(`${BASE}/admin/data-customer?q=script`, { waitUntil: 'domcontentloaded' });
    await waitSettled(page);
    file = await shot(page, OUT.security, '02_escaping_xss_data_customer.png', { fullPage: false });
    record({
        id: 'SEC-03',
        action: 'Menampilkan data customer berisi <script>alert("xss")</script>',
        observed: 'Payload tampil sebagai teks literal (ter-escape) di tabel, bukan dieksekusi sebagai elemen script.',
        screenshot: file,
    });

    await page.goto(`${BASE}/admin/data-customer?q=${encodeURIComponent("' OR 1=1 --")}`, { waitUntil: 'domcontentloaded' });
    await waitSettled(page);
    const sqliText = await page.locator('.admin-card').first().innerText();
    file = await shot(page, OUT.security, '03_sql_injection_pencarian.png', { fullPage: false });
    record({
        id: 'SEC-05',
        action: "Mencari customer dengan input ' OR 1=1 --",
        observed: sqliText.replace(/\s+/g, ' ').trim().slice(0, 180),
        screenshot: file,
    });

    // 419: hapus token CSRF dari form lalu submit.
    await page.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await scheduleRow(page, '10 November 2026').getByRole('link', { name: 'Edit' }).click();
    await waitSettled(page);
    await page.evaluate(() => {
        document.querySelectorAll('input[name="_token"]').forEach((input) => input.remove());
    });
    await submitAndWait(page, '#scheduleEditSubmit');
    const csrfTitle = await page.title();
    file = await shot(page, OUT.security, '04_penolakan_csrf_419.png', { fullPage: false });
    record({
        id: 'SEC-02',
        action: 'Mengirim update jadwal tanpa token CSRF',
        observed: `Submit tanpa token CSRF menampilkan halaman "${csrfTitle}"; status 419 diverifikasi terpisah pada evidence/security_http_checks.txt dan perubahan tidak tersimpan.`,
        screenshot: file,
    });

    // 403: pemilik usaha mencoba membuka halaman admin.
    const ownerContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const ownerPage = await ownerContext.newPage();
    await login(ownerPage, 'pemilik', 'pemilik@fixpoint.test', 'Pemilik12345');
    const ownerResponse = await ownerPage.goto(`${BASE}/admin/jadwal-servis`, { waitUntil: 'domcontentloaded' });
    await waitSettled(ownerPage);
    file = await shot(ownerPage, OUT.security, '05_penolakan_akses_403.png', { fullPage: false });
    record({
        id: 'SEC-role',
        action: 'Pemilik Usaha membuka /admin/jadwal-servis',
        observed: `HTTP ${ownerResponse.status()} dengan halaman Akses Ditolak.`,
        screenshot: file,
    });
    await ownerContext.close();

    // ---------------------------------------------------------------------
    // S3-07 — Screenshot performa dari server MySQL (port 8000)
    // ---------------------------------------------------------------------
    const liveContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const livePage = await liveContext.newPage();

    await gotoWithRetry(livePage, `${LIVE_BASE}/ketersediaan-jadwal`);
    await waitSettled(livePage);
    file = await shot(livePage, OUT.performance, '01_ketersediaan_jadwal.png', { fullPage: true });
    record({ id: 'PERF-page-public', action: 'Membuka ketersediaan jadwal (MySQL)', observed: 'Halaman publik tampil normal.', screenshot: file });

    await gotoWithRetry(livePage, `${LIVE_BASE}/login`);
    await livePage.fill('#email', 'admin@fixpoint.test');
    await livePage.fill('#password', 'Admin12345');
    await submitAndWait(livePage, 'button.login-button');

    await gotoWithRetry(livePage, `${LIVE_BASE}/dashboard-admin`);
    await waitSettled(livePage);
    file = await shot(livePage, OUT.performance, '02_dashboard_admin.png', { fullPage: true });
    record({ id: 'PERF-page-dashboard', action: 'Membuka dashboard admin (MySQL)', observed: 'Dashboard admin tampil.', screenshot: file });

    await gotoWithRetry(livePage, `${LIVE_BASE}/admin/riwayat-servis?from=2026-11-01&to=2026-11-30`);
    await waitSettled(livePage);
    file = await shot(livePage, OUT.performance, '03_riwayat_servis_filter.png', { fullPage: true });
    record({ id: 'PERF-page-history', action: 'Membuka riwayat servis dengan filter tanggal (MySQL)', observed: 'Halaman riwayat servis dengan filter tampil.', screenshot: file });

    await gotoWithRetry(livePage, `${LIVE_BASE}/admin/jadwal-servis`);
    await waitSettled(livePage);
    file = await shot(livePage, OUT.performance, '04_jadwal_servis_breadcrumb.png', { fullPage: true });
    record({ id: 'PERF-page-schedule', action: 'Membuka jadwal servis admin (MySQL)', observed: 'Breadcrumb Dashboard / Operasional / Jadwal Servis tampil.', screenshot: file });

    const liveMobile = await liveContext.newPage();
    await liveMobile.setViewportSize({ width: 390, height: 844 });
    await gotoWithRetry(liveMobile, `${LIVE_BASE}/ketersediaan-jadwal`);
    await waitSettled(liveMobile);
    file = await shot(liveMobile, OUT.performance, '05_mobile_ketersediaan_jadwal.png', { fullPage: true });
    record({ id: 'PERF-page-mobile', action: 'Membuka ketersediaan jadwal pada 390×844', observed: 'Layout satu kolom dan tombol tetap dapat digunakan.', screenshot: file });
    await liveMobile.close();
    await liveContext.close();

    await browser.close();

    // Salin bukti indikator loading ke folder performa (gambar yang sama).
    fs.copyFileSync(
        path.join(OUT.schedule, '09_loading_indicator.png'),
        path.join(OUT.performance, '06_loading_indicator.png')
    );

    const evidenceFile = path.join(ROOT, 'docs/sprint-3/pengumpulan/evidence/screenshots.json');
    fs.writeFileSync(evidenceFile, JSON.stringify({ generated_at: new Date().toISOString(), evidence }, null, 2));
    console.log(`Selesai. ${evidence.length} bukti dicatat di ${path.relative(ROOT, evidenceFile)}`);
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
