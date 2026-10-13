@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
<style>
.hero { padding: 60px 24px; max-width: 1200px; margin: 0 auto; }
.hero-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr); gap: 40px; align-items: center; }
.hero-label { color: #2563eb; font-size: 13px; font-weight: 800; margin-bottom: 10px; text-transform: uppercase; }
.hero-title { font-size: clamp(32px, 4vw, 46px); line-height: 1.12; color: #111827; margin-bottom: 18px; font-weight: 800; }
.hero-text { color: #6b7280; font-size: 16px; margin-bottom: 24px; line-height: 1.7; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
.btn-primary { background: #2563eb; color: #ffffff; padding: 13px 22px; border-radius: 8px; font-size: 14px; font-weight: 700; text-decoration: none; display: inline-block; text-align: center; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #ffffff; color: #2563eb; padding: 13px 22px; border-radius: 8px; font-size: 14px; font-weight: 700; border: 1px solid #dbe2ea; text-decoration: none; display: inline-block; text-align: center; }
.btn-secondary:hover { background: #f8fafc; }
.hero-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; }
.hero-card-title { font-size: 18px; font-weight: 800; color: #111827; margin-bottom: 16px; }
.schedule-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
.schedule-item:last-child { margin-bottom: 0; }
.schedule-date { font-size: 13px; color: #111827; font-weight: 700; margin-bottom: 5px; }
.schedule-status { font-size: 12px; font-weight: 700; color: #15803d; }
.section { padding: 50px 24px; max-width: 1200px; margin: 0 auto; }
.section-title { font-size: 26px; color: #111827; margin-bottom: 8px; font-weight: 800; }
.section-text { color: #6b7280; margin-bottom: 24px; }
.service-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
.service-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 22px; }
.service-card h3 { color: #111827; font-size: 16px; margin-bottom: 8px; }
.service-card p { color: #6b7280; font-size: 13px; }
@media (max-width: 900px) {
    .hero-grid { grid-template-columns: minmax(0, 1fr); gap: 28px; }
    .service-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 600px) {
    .hero { padding: 36px 16px; }
    .hero-grid { gap: 24px; }
    .hero-title { font-size: clamp(28px, 8vw, 36px); }
    .hero-actions { flex-direction: column; }
    .hero-actions a { width: 100%; }
    .hero-card { padding: 18px; }
    .section { padding: 36px 16px; }
    .section-title { font-size: 23px; }
    .service-grid { grid-template-columns: minmax(0, 1fr); gap: 14px; }
    .service-card { padding: 18px; }
}
</style>

<section class="hero">
    <div class="hero-grid">
        <div>
            <div class="hero-label">FixPoint Service</div>
            <h1 class="hero-title">Servis Laptop & PC Lebih Mudah</h1>
            <p class="hero-text">Lakukan booking servis, pilih jadwal yang tersedia, dan pantau perkembangan perbaikan perangkat Anda dengan mudah.</p>
            <div class="hero-actions">
                <a href="{{ route('ketersediaan-jadwal') }}" class="btn-primary">Lihat Ketersediaan Jadwal</a>
                <a href="{{ route('cek-status') }}" class="btn-secondary">Cek Status Service</a>
            </div>
        </div>
        <div class="hero-card">
            <div class="hero-card-title">Ketersediaan Jadwal Servis</div>
            @forelse($schedules as $schedule)
                <div class="schedule-item">
                    <div class="schedule-date">{{ \Carbon\Carbon::parse($schedule->schedule_date)->translatedFormat('l, d F Y') }}</div>
                    <div class="schedule-status">Tersedia ({{ $schedule->max_booking - $schedule->current_booking }} slot)</div>
                </div>
            @empty
                <div class="schedule-item">
                    <div class="schedule-date">Belum ada jadwal tersedia</div>
                    <div style="font-size: 12px; font-weight: 700; color: #dc2626;">Silakan hubungi admin untuk info lebih lanjut.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="section">
    <h2 class="section-title">Layanan FixPoint</h2>
    <p class="section-text">Informasi layanan servis laptop dan PC.</p>
    <div class="service-grid">
        <div class="service-card"><h3>Servis Laptop</h3><p>Pemeriksaan dan perbaikan berbagai masalah pada laptop.</p></div>
        <div class="service-card"><h3>Servis PC</h3><p>Pemeriksaan dan perbaikan perangkat PC desktop.</p></div>
        <div class="service-card"><h3>Booking Service</h3><p>Pilih jadwal yang tersedia dan lakukan booking servis.</p></div>
    </div>
</section>
@endsection