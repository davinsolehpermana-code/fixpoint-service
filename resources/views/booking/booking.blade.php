@extends('layouts.app')

@section('title', 'Booking Service')

@section('content')

    <style>
        .booking-page {
            width: 100%;
            max-width: 1480px;
            margin: 0 auto;
            padding: 32px 28px 56px;
            box-sizing: border-box;
        }

        .booking-header {

            margin-bottom: 28px;

        }



        .booking-label {

            color: #2563eb;

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 6px;

        }



        .booking-title {

            font-size: clamp(26px, 3vw, 32px);

            color: #111827;

            margin-bottom: 8px;

        }



        .booking-description {

            color: #6b7280;

            max-width: 700px;

        }



        .booking-layout {

            display: grid;

            grid-template-columns: minmax(0, 1.9fr) minmax(320px, 0.9fr);

            gap: 24px;

        }



        .booking-card {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 24px;

            margin-bottom: 20px;

        }



        .booking-card-title {

            font-size: 18px;

            color: #111827;

            margin-bottom: 18px;

        }



        .form-grid {

            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 16px;

        }



        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }



        .form-label {

            font-size: 13px;

            font-weight: 700;

            color: #374151;

        }



        .form-control {

            width: 100%;

            padding: 11px 13px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            font-size: 14px;

            background: #ffffff;

            color: #111827;

            outline: none;

            box-sizing: border-box;

        }



        .form-control:focus {

            border-color: #2563eb;

        }



        .service-options {

            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 14px;

        }



        .service-option {

            border: 1px solid #dbe2ea;

            border-radius: 10px;

            padding: 16px;

            cursor: pointer;

            transition: 0.2s;

        }



        .service-option:hover {

            border-color: #2563eb;

            background: #eff6ff;

        }



        .service-option input {

            margin-right: 8px;

        }



        .service-option-title {

            font-size: 15px;

            font-weight: 700;

            color: #111827;

        }



        .service-option-text {

            margin-top: 5px;

            font-size: 13px;

            color: #6b7280;

        }



        .schedule-status {

            display: inline-block;

            margin-top: 8px;

            color: #15803d;

            font-size: 13px;

            font-weight: 700;

        }



        .summary-card {

            position: sticky;

            top: 95px;

        }



        .summary-row {

            display: flex;

            justify-content: space-between;

            gap: 16px;

            padding: 12px 0;

            border-bottom: 1px solid #f0f1f3;

            font-size: 14px;

        }



        .summary-row:last-child {

            border-bottom: none;

        }



        .summary-label {

            color: #6b7280;

        }



        .summary-value {

            color: #111827;

            font-weight: 600;

            text-align: right;

            max-width: 220px;

            word-break: break-word;

            overflow-wrap: break-word;

        }



        .summary-total {

            margin-top: 8px;

            padding-top: 16px;

            border-top: 1px solid #e5e7eb;

            display: flex;

            justify-content: space-between;

            gap: 16px;

        }



        .summary-total strong {

            color: #2563eb;

            font-size: 18px;

        }



        .booking-button {

            display: block;

            width: 100%;

            border: none;

            background: #2563eb;

            color: #ffffff;

            padding: 13px 16px;

            border-radius: 8px;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            margin-top: 20px;

        }



        .booking-button:hover {

            background: #1d4ed8;

        }



        .back-link {

            display: inline-block;

            margin-top: 12px;

            color: #2563eb;

            font-size: 13px;

            font-weight: 600;

            text-align: center;

            width: 100%;

            text-decoration: none;

        }



        .alert-success {

            background: #dcfce7;

            color: #166534;

            border: 1px solid #bbf7d0;

            padding: 12px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;

        }



        .alert-error {

            background: #fee2e2;

            color: #991b1b;

            border: 1px solid #fecaca;

            padding: 12px 16px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;

        }



        /* Mobile Responsive */

        @media (max-width: 960px) {

            .booking-layout {

                grid-template-columns: 1fr;

            }

            .summary-card {

                position: static;

            }

        }



        @media (max-width: 640px) {

            .booking-header {

                padding: 0 4px;

            }

            .booking-title {

                font-size: 26px;

            }

            .form-grid,

            .service-options {

                grid-template-columns: 1fr;

            }

            .booking-card {

                padding: 18px;

            }

            .form-control {

                font-size: 16px;
                /* Prevents zoom on iOS */

                padding: 12px;

            }

            .booking-button {

                padding: 14px;

                font-size: 15px;

            }

        }
    </style>

    <main class="booking-page">

        {{-- ⚠️ TAMBAHAN NOTIFIKASI ERROR SLOT PENUH --}}
        @if (session('error'))
            <div class="alert-error" style="display: flex; align-items: center; gap: 8px; font-weight: 700;">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif



        @if ($errors->any())

            <div class="alert-error">

                <strong>Data belum lengkap.</strong>

                <ul style="margin: 8px 0 0 18px;">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif



        <div class="booking-header">

            <div class="booking-label">FIXPOINT SERVICE</div>

            <h1 class="booking-title">Booking Perbaikan Perangkat</h1>

            <p class="booking-description">Isi data perangkat dan keluhan untuk melakukan booking servis laptop atau PC.</p>

        </div>



        {{-- 🔒 TAMBAHAN ID PADA FORM UNTUK MENCEGAH DOUBLE SUBMIT --}}
        <form action="{{ route('booking.store') }}" method="POST" id="bookingForm">

            @csrf

            <div class="booking-layout">

                <div>

                    {{-- DATA PELANGGAN --}}

                    <div class="booking-card">

                        <h2 class="booking-card-title">Data Pelanggan</h2>

                        <div class="form-grid">

                            <div class="form-group">

                                <label class="form-label">Nama Pelanggan</label>

                                <input type="text" name="nama" id="nama" class="form-control"
                                    placeholder="Masukkan nama" value="{{ old('nama') }}" required>

                            </div>

                            <div class="form-group">

                                <label class="form-label">Nomor Telepon</label>

                                <input type="text" name="telepon" id="telepon" class="form-control"
                                    placeholder="Masukkan nomor telepon" value="{{ old('telepon') }}" required>

                            </div>

                        </div>

                    </div>



                    {{-- SPESIFIKASI PERANGKAT --}}

                    <div class="booking-card">

                        <h2 class="booking-card-title">Spesifikasi Perangkat</h2>

                        <div class="form-grid">

                            <div class="form-group">

                                <label class="form-label">Jenis Perangkat</label>

                                <select name="jenis_perangkat" id="jenis_perangkat" class="form-control" required>

                                    <option value="">Pilih perangkat</option>

                                    <option value="Laptop" {{ old('jenis_perangkat') === 'Laptop' ? 'selected' : '' }}>
                                        Laptop</option>

                                    <option value="PC Desktop"
                                        {{ old('jenis_perangkat') === 'PC Desktop' ? 'selected' : '' }}>PC Desktop</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label class="form-label">Merek / Model</label>

                                <input type="text" name="merek_model" id="merek_model" class="form-control"
                                    placeholder="Contoh: ASUS VivoBook" value="{{ old('merek_model') }}" required>

                            </div>

                        </div>

                    </div>



                    {{-- KATEGORI LAYANAN --}}

                    <div class="booking-card">

                        <h2 class="booking-card-title">Kategori Layanan</h2>

                        <div class="service-options">

                            <label class="service-option">

                                <div>

                                    <input type="radio" name="service" value="laptop" data-service-name="Servis Laptop"
                                        {{ old('service') === 'laptop' ? 'checked' : '' }} required>

                                    <span class="service-option-title">Servis Laptop</span>

                                </div>

                                <p class="service-option-text">Pemeriksaan dan perbaikan laptop.</p>

                            </label>

                            <label class="service-option">

                                <div>

                                    <input type="radio" name="service" value="pc"
                                        data-service-name="Servis PC Desktop"
                                        {{ old('service') === 'pc' ? 'checked' : '' }} required>

                                    <span class="service-option-title">Servis PC Desktop</span>

                                </div>

                                <p class="service-option-text">Pemeriksaan dan perbaikan PC desktop.</p>

                            </label>

                        </div>

                    </div>



                    {{-- DETAIL KELUHAN --}}

                    <div class="booking-card">

                        <h2 class="booking-card-title">Detail Keluhan</h2>

                        <div class="form-group">

                            <label class="form-label">Keluhan Perangkat</label>

                            <textarea name="keluhan" id="keluhan" class="form-control" rows="6"
                                placeholder="Jelaskan masalah pada perangkat..." required>{{ old('keluhan') }}</textarea>

                        </div>

                    </div>



                    {{-- JADWAL SERVIS --}}

                    <div class="booking-card">

                        <h2 class="booking-card-title">Jadwal Servis</h2>

                        <div class="form-group">

                            <label class="form-label">Pilih Jadwal</label>

                            <select name="schedule_id" id="schedule_id" class="form-control" required>

                                <option value="">Pilih jadwal servis</option>


                                @foreach ($schedules as $schedule)
                                    <option value="{{ $schedule->schedule_id }}"
                                        data-date="{{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('d M Y') }}"
                                        data-time="{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}"
                                        {{ old('schedule_id', request('schedule_id')) == $schedule->schedule_id ? 'selected' : '' }}>

                                        {{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l, d F Y') }}
                                        - {{ substr($schedule->start_time, 0, 5) }} s/d
                                        {{ substr($schedule->end_time, 0, 5) }}

                                    </option>
                                @endforeach

                            </select>

                        </div>

                        @if ($schedules->isEmpty())
                            <span class="schedule-status" style="color: #dc2626;">Tidak ada jadwal tersedia.</span>
                        @else
                            <span class="schedule-status">Jadwal tersedia</span>
                        @endif

                    </div>

                    <div class="booking-card">

                        <h2 class="booking-card-title">Metode Pembayaran</h2>

                        <div class="form-group">

                            <label class="form-label" for="payment_method">Pilih metode pembayaran</label>

                            <select name="payment_method" id="payment_method" class="form-control" required>

                                <option value="">Pilih metode pembayaran</option>

                                <option value="CASH" {{ old('payment_method') === 'CASH' ? 'selected' : '' }}>Cash /
                                    Tunai</option>

                                <option value="QRIS" {{ old('payment_method') === 'QRIS' ? 'selected' : '' }}>QRIS
                                </option>

                            </select>

                        </div>

                    </div>

                </div>



                {{-- RINGKASAN BOOKING --}}

                <div>

                    <div class="booking-card summary-card">

                        <h2 class="booking-card-title">Ringkasan Booking</h2>

                        <div class="summary-row">

                            <span class="summary-label">Nama</span>

                            <span class="summary-value" id="summary-nama">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Telepon</span>

                            <span class="summary-value" id="summary-telepon">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Perangkat</span>

                            <span class="summary-value" id="summary-perangkat">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Merek / Model</span>

                            <span class="summary-value" id="summary-model">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Layanan</span>

                            <span class="summary-value" id="summary-layanan">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Jadwal</span>

                            <span class="summary-value" id="summary-jadwal">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Keluhan</span>

                            <span class="summary-value" id="summary-keluhan">-</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Status Jadwal</span>

                            <span class="summary-value" id="summary-status-jadwal">Belum dipilih</span>

                        </div>

                        <div class="summary-row">

                            <span class="summary-label">Metode Pembayaran</span>

                            <span class="summary-value" id="summary-payment-method">-</span>

                        </div>

                        <div class="summary-total">

                            <span>Estimasi Pengerjaan</span>

                            <strong>-</strong>

                        </div>

                        {{-- 🔒 TAMBAHAN ID PADA TOMBOL SUBMIT --}}
                        <button type="submit" id="submitBtn" class="booking-button"
                            {{ $schedules->isEmpty() ? 'disabled' : '' }}>Booking Service</button>

                        <a href="/ketersediaan-jadwal" class="back-link">Kembali ke Ketersediaan Jadwal</a>

                    </div>

                </div>

            </div>

        </form>



        <script>
            const namaInput = document.getElementById('nama');

            const teleponInput = document.getElementById('telepon');

            const perangkatInput = document.getElementById('jenis_perangkat');

            const modelInput = document.getElementById('merek_model');

            const keluhanInput = document.getElementById('keluhan');

            const scheduleInput = document.getElementById('schedule_id');

            const paymentMethodInput = document.getElementById('payment_method');



            const summaryNama = document.getElementById('summary-nama');

            const summaryTelepon = document.getElementById('summary-telepon');

            const summaryPerangkat = document.getElementById('summary-perangkat');

            const summaryModel = document.getElementById('summary-model');

            const summaryLayanan = document.getElementById('summary-layanan');

            const summaryJadwal = document.getElementById('summary-jadwal');

            const summaryKeluhan = document.getElementById('summary-keluhan');
            const summaryStatusJadwal = document.getElementById('summary-status-jadwal');
            const summaryPaymentMethod = document.getElementById('summary-payment-method');



            let updateTimeout;

            function updateSummary() {

                clearTimeout(updateTimeout);

                updateTimeout = setTimeout(() => {

                    summaryNama.textContent = namaInput.value.trim() || '-';

                    summaryTelepon.textContent = teleponInput.value.trim() || '-';

                    summaryPerangkat.textContent = perangkatInput.value || '-';

                    summaryModel.textContent = modelInput.value.trim() || '-';

                    summaryKeluhan.textContent = keluhanInput.value.trim() || '-';
                    summaryPaymentMethod.textContent = paymentMethodInput.value === 'CASH' ?
                        'Cash / Tunai' :
                        (paymentMethodInput.value === 'QRIS' ? 'QRIS' : '-');



                    const selectedService = document.querySelector('input[name="service"]:checked');

                    if (selectedService) {

                        summaryLayanan.textContent = selectedService.dataset.serviceName;

                    } else {

                        summaryLayanan.textContent = '-';

                    }



                    const selectedSchedule = scheduleInput.options[scheduleInput.selectedIndex];

                    if (selectedSchedule && selectedSchedule.value) {

                        const date = selectedSchedule.dataset.date;

                        const time = selectedSchedule.dataset.time;

                        const scheduleText = date + ' - ' + time;
                        summaryJadwal.textContent = scheduleText;
                        summaryStatusJadwal.textContent = 'Tersedia';
                        summaryStatusJadwal.style.color = '#15803d';
                    } else {
                        summaryJadwal.textContent = '-';
                        const hasSchedules = scheduleInput.options.length > 1;
                        summaryStatusJadwal.textContent = hasSchedules ? 'Belum dipilih' : 'Tidak tersedia';
                        summaryStatusJadwal.style.color = hasSchedules ? '#6b7280' : '#dc2626';
                    }

                }, 50);

            }



            if (namaInput) namaInput.addEventListener('input', updateSummary);

            if (teleponInput) teleponInput.addEventListener('input', updateSummary);

            if (perangkatInput) perangkatInput.addEventListener('change', updateSummary);

            if (modelInput) modelInput.addEventListener('input', updateSummary);

            if (keluhanInput) keluhanInput.addEventListener('input', updateSummary);

            if (scheduleInput) scheduleInput.addEventListener('change', updateSummary);

            if (paymentMethodInput) paymentMethodInput.addEventListener('change', updateSummary);



            document.querySelectorAll('input[name="service"]').forEach(function(radio) {

                radio.addEventListener('change', updateSummary);

            });



            updateSummary();


            // 🔒 TAMBAHAN SCRIPT UNTUK MENCEGAH DOUBLE SUBMIT / KLIK BERULANG KALI
            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                const submitBtn = document.getElementById('submitBtn');

                // Nonaktifkan tombol agar tidak bisa diklik dua kali
                submitBtn.disabled = true;

                // Ubah tampilan tombol menjadi status loading
                submitBtn.style.opacity = '0.7';
                submitBtn.style.cursor = 'not-allowed';
                submitBtn.innerText = 'Memproses Booking...';
            });
        </script>



        {{-- POP-UP BOOKING BERHASIL --}}

        @if (session('success') && session('service_id_code'))
            <div id="successModal"
                style="position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.5);">

                <div
                    style="background-color: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); padding: 24px; max-width: 28rem; width: 90%; text-align: center; border: 1px solid #e5e7eb;">

                    <h3 style="font-size: 1.25rem; font-weight: 700; color: #16a34a; margin-bottom: 8px;">Booking Berhasil!
                    </h3>

                    <p style="color: #374151; margin-bottom: 16px; font-size: 14px;">{{ session('success') }}</p>

                    <div
                        style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px; border-radius: 8px; margin-bottom: 20px;">

                        <p style="font-size: 13px; color: #166534; margin-bottom: 4px; font-weight: 600;">Service ID Anda:
                        </p>

                        <p
                            style="font-size: 1.5rem; font-family: monospace; font-weight: 800; color: #15803d; letter-spacing: 1px;">
                            {{ session('service_id_code') }}</p>

                        <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">Simpan kode ini untuk mengecek status
                            servis Anda.</p>

                    </div>

                    <button onclick="document.getElementById('successModal').style.display='none'"
                        style="background-color: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; width: 100%;">

                        Tutup

                    </button>

                </div>

            </div>
        @endif

    </main>

@endsection
