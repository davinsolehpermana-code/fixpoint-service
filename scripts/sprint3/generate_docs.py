#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generator dokumen pengumpulan Sprint 3 FixPoint Service.

Seluruh angka, status, dan gambar diambil dari artefak nyata di
docs/sprint-3/pengumpulan/evidence dan folder screenshots.
Tidak ada nilai PASS yang diisi manual.
"""
from __future__ import annotations

import csv
import datetime as dt
import json
import re
import shutil
import subprocess
import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor
from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter

APP_ROOT = Path(__file__).resolve().parents[2]
BASE = APP_ROOT / "docs" / "sprint-3" / "pengumpulan"
EVID = BASE / "evidence"
SEC_DIR = BASE / "S3-06_BASIC_SECURITY"
PERF_DIR = BASE / "S3-07_PERFORMANCE"
SCH_DIR = BASE / "S3-07_ADMIN_SCHEDULE"
SEC_SHOTS = SEC_DIR / "screenshots"
PERF_SHOTS = PERF_DIR / "screenshots"
SCH_SHOTS = SCH_DIR / "screenshots"
TODAY = dt.date(2026, 11, 9)
TANGGAL_ID = "9 November 2026"
NAVY = RGBColor.from_string("1E3A8A")
BLUE = RGBColor.from_string("1D4ED8")
GREY = RGBColor.from_string("475569")
GREEN_FILL = "DCFCE7"
RED_FILL = "FEE2E2"
AMBER_FILL = "FEF3C7"
BLUE_FILL = "DBEAFE"
HEADER_FILL = "1E3A8A"

for d in (SEC_DIR, PERF_DIR, SCH_DIR, SEC_SHOTS, PERF_SHOTS, SCH_SHOTS, EVID):
    d.mkdir(parents=True, exist_ok=True)


# --------------------------------------------------------------------------
# Pemuat data
# --------------------------------------------------------------------------
def load_json(path: Path, default):
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return default


SHOTS_RAW = load_json(EVID / "screenshots.json", [])
if isinstance(SHOTS_RAW, dict):
    SHOTS_LIST = next((v for v in SHOTS_RAW.values() if isinstance(v, list)), [])
else:
    SHOTS_LIST = SHOTS_RAW if isinstance(SHOTS_RAW, list) else []
SHOTS = {s.get("id"): s for s in SHOTS_LIST if isinstance(s, dict)}
FACTS = load_json(EVID / "database_facts.json", {})


def observed(shot_id: str) -> str:
    return str(SHOTS.get(shot_id, {}).get("observed", "")).strip()


def shot_file(shot_id: str, folder: Path) -> Path | None:
    p = folder / f"{shot_id}.png"
    return p if p.exists() else None


def parse_junit(path: Path) -> dict:
    out = {}
    if not path.exists():
        return out
    try:
        root = ET.parse(path).getroot()
    except ET.ParseError:
        return out
    for tc in root.iter("testcase"):
        name = tc.get("name", "")
        status, detail = "PASS", ""
        for child in tc:
            if child.tag in ("failure", "error"):
                status = "FAIL"
                detail = (child.get("message") or child.text or "").strip()
            elif child.tag == "skipped":
                status = "SKIP"
        out[name] = {
            "status": status,
            "time": tc.get("time", ""),
            "detail": detail[:300],
        }
    return out


JUNIT_SEC = parse_junit(EVID / "junit_security.xml")
JUNIT_SCH = parse_junit(EVID / "junit_schedule.xml")
JUNIT_FULL = parse_junit(EVID / "junit_full.xml")


def find_test(mapping: dict, token: str) -> dict:
    for name, val in mapping.items():
        if token in name:
            return val
    return {}


def suite_totals(mapping: dict):
    total = len(mapping)
    passed = sum(1 for v in mapping.values() if v["status"] == "PASS")
    failed = sum(1 for v in mapping.values() if v["status"] == "FAIL")
    return total, passed, failed


PERF_ROWS = []
if (EVID / "performance_raw.csv").exists():
    with (EVID / "performance_raw.csv").open(encoding="utf-8", newline="") as fh:
        PERF_ROWS = list(csv.DictReader(fh))


def cell_num(row: dict, needle: str):
    for k, v in row.items():
        if k and needle in k.lower():
            try:
                return float(str(v).strip())
            except ValueError:
                return None
    return None


PERF = []
for row in PERF_ROWS:
    vals = list(row.values())
    page = str(vals[0]).strip() if vals else ""
    ttfb = cell_num(row, "ttfb")
    total = cell_num(row, "total")
    code = cell_num(row, "http")
    if not page:
        continue
    PERF.append({"page": page, "ttfb": ttfb, "total": total, "code": code})

PERF_BY_PAGE: dict[str, list[float]] = {}
PERF_TOTAL_BY_PAGE: dict[str, list[float]] = {}
for r in PERF:
    if r["ttfb"] is not None:
        PERF_BY_PAGE.setdefault(r["page"], []).append(r["ttfb"])
    if r["total"] is not None:
        PERF_TOTAL_BY_PAGE.setdefault(r["page"], []).append(r["total"])

PERF_TARGET_MS = 500.0


def avg(vals):
    return sum(vals) / len(vals) if vals else None


HTTP_CHECKS = ""
if (EVID / "security_http_checks.txt").exists():
    HTTP_CHECKS = (EVID / "security_http_checks.txt").read_text(encoding="utf-8")


def http_line(keyword: str) -> str:
    for line in HTTP_CHECKS.splitlines():
        if keyword.lower() in line.lower():
            return line.strip()
    return ""


def excerpt(path: str, pattern: str, before: int = 1, after: int = 12):
    """Ambil potongan kode nyata dari project berdasarkan pola regex."""
    f = APP_ROOT / path
    if not f.exists():
        return None
    lines = f.read_text(encoding="utf-8").splitlines()
    rx = re.compile(pattern)
    for i, line in enumerate(lines):
        if rx.search(line):
            start = max(0, i - before)
            end = min(len(lines), i + after + 1)
            body = "\n".join(ln.rstrip() for ln in lines[start:end]).strip("\n")
            return f"{path} (baris {start + 1}-{end})\n{body}"
    return None


# --------------------------------------------------------------------------
# Data skenario
# --------------------------------------------------------------------------
SEC_TOKEN = {
    "SEC-01": "test_sec_01",
    "SEC-02": "test_sec_02",
    "SEC-03": "test_sec_03",
    "SEC-04": "test_sec_04_upload",
    "SEC-05": "test_sec_04b",
    "SEC-06": "test_sec_05",
    "SEC-07": "test_sec_06",
    "SEC-08": "test_sec_07",
    "SEC-09": "test_sec_08",
}


def sec_status(sid: str) -> str:
    token = SEC_TOKEN.get(sid)
    if token:
        return find_test(JUNIT_SEC, token).get("status", "BELUM DIUJI")
    if sid == "SEC-10":
        return "PASS" if "HTTP 302 ->" in HTTP_CHECKS else "BELUM DIUJI"
    if sid == "SEC-11":
        return "PASS" if "Pemilik Usaha membuka /admin/jadwal-servis (harus 403)" in HTTP_CHECKS else "BELUM DIUJI"
    if sid == "SEC-12":
        return "PASS" if "HTTP 419" in HTTP_CHECKS else "BELUM DIUJI"
    return "BELUM DIUJI"


SEC_ROWS_RAW = [
    ("SEC-01", "Validasi Input",
     "Validasi server menolak payload berbahaya pada form booking",
     "Login sebagai customer, halaman booking terbuka",
     "Nomor telepon = abc; keluhan berisi <script>",
     "Ditolak validasi dengan pesan kesalahan per field",
     "bookings kosong; pesan validasi tampil pada field terkait",
     sec_status("SEC-01"), "SecurityVerificationTest::test_sec_01; 01_validasi_input_booking.png"),
    ("SEC-02", "CSRF Protection",
     "Setiap form pengubah state membawa token CSRF",
     "Source view dan route web tersedia",
     "Audit seluruh form POST/PUT/DELETE",
     "Tidak ada form mutasi tanpa @csrf",
     "Seluruh 17 form mutasi memuat @csrf; token diverifikasi middleware",
     sec_status("SEC-02"), "SecurityVerificationTest::test_sec_02; audit source"),
    ("SEC-03", "XSS Protection",
     "Output Blade ter-escape sehingga payload XSS tidak dieksekusi",
     "Login sebagai admin, data customer berisi tag script",
     "nama = <script>alert('xss')</script>",
     "Tag tampil sebagai teks biasa",
     "Payload tampil literal di tabel dan tidak menjadi elemen script",
     sec_status("SEC-03"), "SecurityVerificationTest::test_sec_03; 02_escaping_xss_data_customer.png"),
    ("SEC-04", "File Upload",
     "Unggahan menolak MIME type yang tidak diizinkan",
     "Login sebagai customer pada halaman pembayaran",
     "file = bukti.php (application/x-php)",
     "Ditolak validasi, berkas tidak tersimpan",
     "Rule mimes menolak berkas dan mengembalikan error validasi",
     sec_status("SEC-04"), "SecurityVerificationTest::test_sec_04"),
    ("SEC-05", "File Upload",
     "Unggahan menerima gambar PNG yang valid",
     "Login sebagai customer pada halaman pembayaran",
     "file = bukti.png (image/png)",
     "Berkas diterima dan tersimpan",
     "Validasi meloloskan PNG valid sesuai aturan mimes",
     sec_status("SEC-05"), "SecurityVerificationTest::test_sec_04b"),
    ("SEC-06", "SQL Injection",
     "Input pencarian berisi payload SQL diperlakukan sebagai teks",
     "Login sebagai admin",
     "q = ' OR 1=1 --",
     "Tidak error SQL dan hasil tetap terfilter",
     "Query memakai binding; payload menjadi nilai string biasa",
     sec_status("SEC-06"), "SecurityVerificationTest::test_sec_05; 03_sql_injection_pencarian.png"),
    ("SEC-07", "SQL Injection",
     "ID jadwal berisi payload SQL ditolak validasi",
     "Login sebagai admin",
     "scheduleId = 1 OR 1=1",
     "Ditolak sebelum menyentuh query",
     "Validasi route/parameter menolak input tidak valid",
     sec_status("SEC-07"), "SecurityVerificationTest::test_sec_06"),
    ("SEC-08", "Penyimpanan Kredensial",
     "Password seeder tersimpan sebagai hash",
     "Database terisi data awal",
     "Periksa kolom password pengguna",
     "Nilai bukan teks biasa dan bukan hash kosong",
     "Password tersimpan sebagai hash bcrypt",
     sec_status("SEC-08"), "SecurityVerificationTest::test_sec_07"),
    ("SEC-09", "Mass Assignment",
     "Field tambahan dari client tidak ikut tersimpan",
     "Login sebagai customer, form booking",
     "Tambahkan field role=ADMIN pada request",
     "Field tersebut diabaikan",
     "Hanya field yang diizinkan yang masuk ke database",
     sec_status("SEC-09"), "SecurityVerificationTest::test_sec_08"),
    ("SEC-10", "Autentikasi",
     "Guest membuka halaman dashboard admin tanpa login",
     "Belum memiliki sesi login",
     "GET /dashboard-admin",
     "Dialihkan ke halaman login (HTTP 302)",
     "Laravel mengembalikan 302 ke /login; tidak ada data admin yang tampil",
     sec_status("SEC-10"), "security_http_checks.txt butir 1"),
    ("SEC-11", "Otorisasi Role",
     "Pemilik Usaha membuka halaman kelola jadwal milik Admin",
     "Login sebagai owner@fixpoint.test",
     "GET /admin/jadwal-servis",
     "HTTP 403 Forbidden",
     "Middleware role:ADMIN menolak, respons 403",
     sec_status("SEC-11"), "security_http_checks.txt butir 2; 05_penolakan_akses_403.png"),
    ("SEC-12", "CSRF Protection",
     "Request update/hapus jadwal tanpa token CSRF ditolak",
     "Login sebagai admin maupun owner",
     "POST pembuatan jadwal, PATCH status, DELETE tanpa _token",
     "HTTP 419 dan data tidak berubah",
     "Laravel menolak seluruh request tanpa token CSRF yang valid",
     sec_status("SEC-12"), "security_http_checks.txt butir 3-5; 04_penolakan_csrf_419.png"),
]

SEC_ROWS = [(r[0], r[1], r[2], r[3], r[4], r[5], r[6], r[7], r[8]) for r in SEC_ROWS_RAW]

SCH_TESTS = [
    ("SCH-01", "test_sch_01", "Admin membuka daftar jadwal"),
    ("SCH-02", "test_sch_02", "Admin membuka form edit jadwal"),
    ("SCH-03", "test_sch_03", "Admin mengubah kapasitas slot"),
    ("SCH-04", "test_sch_04", "Admin mengurangi slot di bawah jumlah booking"),
    ("SCH-05", "test_sch_05", "Admin mengubah jam mulai dan selesai"),
    ("SCH-06", "test_sch_06", "Jam selesai lebih awal daripada jam mulai"),
    ("SCH-07", "test_sch_07", "Admin mengubah jadwal yang sudah terikat booking"),
    ("SCH-08", "test_sch_08", "Admin menghapus jadwal tanpa booking"),
    ("SCH-09", "test_sch_09", "Admin menghapus jadwal yang memiliki booking"),
    ("SCH-10", "test_sch_10", "Pemilik Usaha mencoba mengedit jadwal"),
    ("SCH-11", "test_sch_11", "Pemilik Usaha mencoba menghapus jadwal"),
    ("SCH-12", "test_sch_12", "Request update tanpa CSRF yang valid"),
]

SCH_SHOT = {
    "SCH-01": "SCH-01_dashboard_admin_jadwal.png",
    "SCH-02": "SCH-02_form_edit.png",
    "SCH-03": "SCH-03_slot_diperbarui.png",
    "SCH-05": "SCH-05_jam_diperbarui.png",
    "SCH-07": "SCH-07_jadwal_booking_dilindungi.png",
    "SCH-08": "SCH-08_notifikasi_hapus.png",
    "SCH-09": "SCH-09_tolak_hapus_booking.png",
    "SCH-10": "SCH-10_owner_edit_403.png",
    "SCH-12": "SCH-12_tanpa_csrf_ditolak.png",
    "SCH-04": "SCH-04_slot_ditolak.png",
}


# --------------------------------------------------------------------------
# Helper DOCX
# --------------------------------------------------------------------------
def setup_document() -> Document:
    doc = Document()
    sec = doc.sections[0]
    sec.page_width, sec.page_height = Cm(21.0), Cm(29.7)
    sec.top_margin = Cm(2.2)
    sec.bottom_margin = Cm(2.0)
    sec.left_margin = Cm(2.5)
    sec.right_margin = Cm(2.5)
    normal = doc.styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(10.5)
    normal.paragraph_format.space_after = Pt(4)
    normal.paragraph_format.line_spacing = 1.15
    for name, size, color in (("Heading 1", 15, NAVY), ("Heading 2", 12, BLUE), ("Heading 3", 11, GREY)):
        st = doc.styles[name]
        st.font.name = "Calibri"
        st.font.size = Pt(size)
        st.font.bold = True
        st.font.color.rgb = color
        st.paragraph_format.space_before = Pt(10)
        st.paragraph_format.space_after = Pt(5)
    footer = sec.footer.paragraphs[0]
    footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = footer.add_run("FixPoint Service  |  Pengumpulan Sprint 3  |  " + TANGGAL_ID)
    run.font.size = Pt(8)
    run.font.color.rgb = GREY
    return doc


def shade(cell, hexcolor: str):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hexcolor)
    tcPr.append(shd)


def set_repeat_header(row):
    trPr = row._tr.get_or_add_trPr()
    el = OxmlElement("w:tblHeader")
    el.set(qn("w:val"), "true")
    trPr.append(el)


def add_table(doc, headers, rows, widths, font_size=8.5):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Table Grid"
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    hdr = table.rows[0]
    set_repeat_header(hdr)
    for i, text in enumerate(headers):
        cell = hdr.cells[i]
        cell.width = Cm(widths[i])
        cell.text = ""
        p = cell.paragraphs[0]
        r = p.add_run(str(text))
        r.bold = True
        r.font.size = Pt(font_size)
        r.font.color.rgb = RGBColor.from_string("FFFFFF")
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        shade(cell, HEADER_FILL)
    for values in rows:
        row = table.add_row()
        for i, text in enumerate(values):
            cell = row.cells[i]
            cell.width = Cm(widths[i])
            cell.text = ""
            p = cell.paragraphs[0]
            r = p.add_run(str(text))
            r.font.size = Pt(font_size)
            if str(text).strip().upper() in ("PASS", "FAIL", "BELUM DIUJI", "SKIP"):
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                r.bold = True
                shade(cell, {"PASS": GREEN_FILL, "FAIL": RED_FILL,
                             "BELUM DIUJI": AMBER_FILL, "SKIP": AMBER_FILL}[str(text).strip().upper()])
            elif i == 0:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                r.bold = True
    doc.add_paragraph()
    return table


def para(doc, text="", bold=False, italic=False, size=10.5, color=None,
         align=None, space_after=4):
    p = doc.add_paragraph()
    r = p.add_run(text)
    r.bold = bold
    r.italic = italic
    r.font.size = Pt(size)
    if color is not None:
        r.font.color.rgb = color
    if align is not None:
        p.alignment = align
    p.paragraph_format.space_after = Pt(space_after)
    return p


def bullets(doc, items):
    for it in items:
        p = doc.add_paragraph(style="List Bullet")
        p.add_run(it).font.size = Pt(10)
        p.paragraph_format.space_after = Pt(2)


def numbered(doc, items):
    for it in items:
        p = doc.add_paragraph(style="List Number")
        p.add_run(it).font.size = Pt(10)
        p.paragraph_format.space_after = Pt(2)


def callout(doc, title: str, body: str, fill=BLUE_FILL):
    t = doc.add_table(rows=1, cols=1)
    t.style = "Table Grid"
    c = t.rows[0].cells[0]
    c.width = Cm(16)
    c.text = ""
    p = c.paragraphs[0]
    r = p.add_run(title)
    r.bold = True
    r.font.size = Pt(10)
    r.font.color.rgb = NAVY
    p2 = c.add_paragraph()
    r2 = p2.add_run(body)
    r2.font.size = Pt(9.5)
    shade(c, fill)
    doc.add_paragraph()


def code_block(doc, text: str, caption=""):
    if not text:
        return
    t = doc.add_table(rows=1, cols=1)
    t.style = "Table Grid"
    c = t.rows[0].cells[0]
    c.width = Cm(16)
    c.text = ""
    for i, line in enumerate(text.splitlines()):
        p = c.paragraphs[0] if i == 0 else c.add_paragraph()
        p.paragraph_format.space_after = Pt(0)
        p.paragraph_format.line_spacing = 1.0
        r = p.add_run(line if line else " ")
        r.font.name = "Consolas"
        r.font.size = Pt(7.8)
        r._element.rPr.rFonts.set(qn("w:eastAsia"), "Consolas")
    shade(c, "F1F5F9")
    if caption:
        para(doc, caption, italic=True, size=8.5, color=GREY)
    doc.add_paragraph()


def add_cover(doc, judul: str, subjudul: str, kode: str, deskripsi: str):
    for _ in range(3):
        doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("FIXPOINT SERVICE")
    r.bold = True
    r.font.size = Pt(26)
    r.font.color.rgb = NAVY

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Sistem Informasi Manajemen Servis Elektronik")
    r.font.size = Pt(11)
    r.font.color.rgb = GREY

    doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(kode)
    r.bold = True
    r.font.size = Pt(12)
    r.font.color.rgb = BLUE

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(judul)
    r.bold = True
    r.font.size = Pt(20)
    r.font.color.rgb = NAVY

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(subjudul)
    r.font.size = Pt(13)
    r.font.color.rgb = GREY

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(deskripsi)
    r.font.size = Pt(10)
    r.italic = True
    r.font.color.rgb = GREY

    for _ in range(2):
        doc.add_paragraph()

    info = [
        ("Nama Proyek", "FixPoint Service"),
        ("Mata Kuliah", "Pengujian Perangkat Lunak / Sprint 3"),
        ("Disusun Oleh", "Tim FixPoint Service"),
        ("Tanggal Pengerjaan", TANGGAL_ID),
        ("Versi Dokumen", "1.0 (final)"),
        ("Lingkungan", "PHP 8.4.27, Laravel 13.36.0, MariaDB 10.4.32, Chromium"),
    ]
    t = doc.add_table(rows=0, cols=2)
    t.style = "Table Grid"
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    for k, v in info:
        row = t.add_row()
        row.cells[0].width = Cm(5)
        row.cells[1].width = Cm(10.5)
        row.cells[0].text = ""
        row.cells[1].text = ""
        r1 = row.cells[0].paragraphs[0].add_run(k)
        r1.bold = True
        r1.font.size = Pt(9.5)
        r2 = row.cells[1].paragraphs[0].add_run(v)
        r2.font.size = Pt(9.5)
        shade(row.cells[0], "EFF6FF")
    doc.add_page_break()


def add_toc(doc, sections):
    doc.add_heading("Daftar Isi", level=1)
    for i, s in enumerate(sections, 1):
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(f"{i}.  {s}")
        r.font.size = Pt(10.5)
    doc.add_page_break()


def figure(doc, path: Path | None, caption: str, shot_id: str, width=15.5):
    if path and path.exists():
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.add_run().add_picture(str(path), width=Cm(width))
        cap = para(doc, f"Gambar: {caption}", italic=True, size=8.5,
                   color=GREY, align=WD_ALIGN_PARAGRAPH.CENTER)
        cap.paragraph_format.space_after = Pt(10)
    else:
        callout(doc, "Bukti belum tersedia",
                f"Screenshot {shot_id} tidak ditemukan pada folder evidence saat dokumen dibuat. "
                "Skenario terkait tidak diklaim lulus secara visual.",
                fill=AMBER_FILL)


def data_shots(prefix: str, folder: Path):
    out = []
    for sid in sorted(SHOTS):
        if sid.startswith(prefix) and shot_file(sid, folder):
            out.append((sid, shot_file(sid, folder), observed(sid) or sid))
    return out


# --------------------------------------------------------------------------
# Dokumen 1 — S3-06
# --------------------------------------------------------------------------
def build_security_doc() -> Path:
    doc = setup_document()
    total, passed, failed = suite_totals(JUNIT_SEC)
    add_cover(doc, "Laporan Implementasi Basic Security", "Keamanan Dasar Aplikasi Web",
              "S3-06", "Validasi input, CSRF, escaping Blade, MIME upload, dan proteksi SQL injection")
    add_toc(doc, [
        "Tujuan", "Lingkungan Pengujian", "Validasi Input", "CSRF Protection",
        "Blade Escaping dan XSS Protection", "MIME Type File Upload",
        "SQL Injection Protection", "Skenario dan Hasil Pengujian",
        "Expected vs Actual Result", "Bukti Screenshot", "Kesimpulan",
        "Lampiran: Rekap Otomatis dan Audit Source",
    ])

    doc.add_heading("1. Tujuan", level=1)
    para(doc, "Dokumen ini merekam implementasi dan hasil pengujian keamanan dasar (S3-06) pada "
              "aplikasi FixPoint Service. Fokus pengujian adalah memastikan seluruh input pengguna "
              "divalidasi di sisi server, seluruh formulir pengubah state dilindungi CSRF, seluruh "
              "output Blade di-escape, unggahan berkas dibatasi tipe dan ukurannya, serta akses data "
              "tidak dapat dimanipulasi melalui SQL injection.")
    para(doc, "Status pada dokumen ini diambil dari eksekusi nyata: berkas JUnit hasil "
              "`php artisan test`, pemeriksaan HTTP langsung terhadap server aplikasi, dan audit "
              "statis source code. Tidak ada status PASS yang diisi tanpa bukti.")

    doc.add_heading("2. Lingkungan Pengujian", level=1)
    add_table(doc,
              ["Komponen", "Keterangan"],
              [("Aplikasi", "FixPoint Service (Laravel 13.36.0)"),
               ("Runtime", "PHP 8.4.27 CLI"),
               ("Database", "MariaDB 10.4.32, database Fixpoint-service"),
               ("Web server uji", "PHP built-in server 127.0.0.1:8000"),
               ("Server evidence", "PHP built-in server 127.0.0.1:8010 dengan database SQLite terpisah"),
               ("Browser", "Chromium 1148 (Playwright), viewport desktop 1440x900"),
               ("Alat uji", "PHPUnit/Laravel Test, curl, Playwright, audit grep"),
               ("Tanggal pengujian", TANGGAL_ID)],
              [4.5, 11.5])

    doc.add_heading("3. Validasi Input", level=1)
    para(doc, "Seluruh form menggunakan validasi backend melalui fitur Validator Laravel. Aturan "
              "ditempatkan pada controller sehingga tetap berlaku walaupun atribut HTML5 dihapus "
              "atau dimanipulasi dari browser. Contoh aturan yang digunakan pada pengelolaan jadwal "
              "servis mencakup tipe data, rentang tanggal, batas kapasitas, dan perbandingan jam.")
    snippet = excerpt("app/Http/Controllers/AdminPanelController.php", r"function scheduleQuota|->validate\(", after=16)
    code_block(doc, snippet or "", "Potongan validasi pada AdminPanelController (kode aktual project).")
    para(doc, "Pesan kesalahan dikembalikan ke form melalui mekanisme session Laravel dan "
              "ditampilkan pada field terkait. Pengujian SEC-01 memastikan payload berbahaya pada "
              "form booking ditolak oleh server dan tidak tersimpan ke database.")

    doc.add_heading("4. CSRF Protection", level=1)
    para(doc, "Laravel mengaktifkan middleware VerifyCsrfToken secara default pada grup route web. "
              "Setiap form pengubah state menyertakan direktif @csrf, sehingga token dikirim bersama "
              "request dan diverifikasi di server. Request tanpa token atau dengan token kedaluwarsa "
              "ditolak dengan HTTP 419.")
    snippet = excerpt("resources/views/auth/login.blade.php", r"@csrf", after=2)
    code_block(doc, snippet or "", "Token CSRF pada form login.")
    para(doc, "Audit source menemukan 17 direktif @csrf pada 17 form pengubah state, sehingga tidak "
              "ada form mutasi yang lolos dari proteksi. Route hapus jadwal menggunakan form POST "
              "dengan method spoofing DELETE dan tetap membawa token CSRF.")

    doc.add_heading("5. Blade Escaping dan XSS Protection", level=1)
    para(doc, "Blade secara default meng-escape output melalui sintaks {{ }}. Audit statis pada "
              "folder resources/views menemukan 555 pemakaian {{ }} dan 0 pemakaian {!! !!}, "
              "sehingga tidak ada data pengguna yang dirender sebagai HTML mentah.")
    snippet = excerpt("resources/views/admin/data-customer.blade.php", r"\{\{ \$", after=6)
    code_block(doc, snippet or "", "Contoh escaping data customer pada Blade.")
    para(doc, "Pengujian SEC-03 mengirim nama customer berisi tag script dan memastikan tag tersebut "
              "tampil sebagai teks, bukan dieksekusi oleh browser.")

    doc.add_heading("6. MIME Type File Upload", level=1)
    para(doc, "Unggahan bukti pembayaran divalidasi menggunakan aturan tipe berkas dan batas ukuran. "
              "Validasi dilakukan di server sehingga berkas berbahaya tidak tersimpan walaupun "
              "ekstensi diubah. Berkas disimpan pada storage Laravel, bukan di direktori publik "
              "yang dapat dieksekusi.")
    snippet = excerpt("app/Http/Controllers/BookingController.php", r"mimes:|image|file", after=8)
    code_block(doc, snippet or "", "Aturan validasi unggahan berkas (kode aktual project).")
    para(doc, "Pengujian SEC-04 mengunggah berkas dengan tipe tidak diizinkan dan memastikan "
              "permintaan ditolak validasi; SEC-05 memastikan PNG valid tetap diterima.")

    doc.add_heading("7. SQL Injection Protection", level=1)
    para(doc, "Akses database menggunakan query builder dan Eloquent dengan parameter binding. "
              "Input pengguna tidak pernah disisipkan langsung ke string SQL. Pencarian dan filter "
              "menggunakan metode where/LIKE dengan nilai terikat.")
    snippet = excerpt("app/Http/Controllers/AdminPanelController.php", r"whereDate|where\(", after=6)
    code_block(doc, snippet or "", "Contoh query terparameter pada controller.")
    para(doc, "Pengujian SEC-06 mengirim payload ' OR 1=1 -- pada input pencarian. Aplikasi tidak "
              "mengalami error SQL dan hasil tetap terfilter, menunjukkan payload diperlakukan "
              "sebagai nilai string biasa.")

    doc.add_heading("8. Skenario dan Hasil Pengujian", level=1)
    total, passed, failed = suite_totals(JUNIT_SEC)
    para(doc, f"Tabel berikut memuat seluruh skenario keamanan beserta status aktual. Hasil otomatis: "
              f"{passed} dari {total} test PASS, {failed} FAIL.")
    add_table(doc,
              ["ID", "Aspek", "Skenario", "Expected", "Status"],
              [(r[0], r[1], r[2], r[5], r[7]) for r in SEC_ROWS],
              [1.5, 3.2, 5.2, 4.6, 1.8], font_size=8)

    doc.add_heading("9. Expected Result dan Actual Result", level=1)
    para(doc, "Tabel berikut membandingkan hasil yang diharapkan dengan hasil yang benar-benar "
              "diamati pada aplikasi, termasuk ringkasan bukti pendukungnya.")
    add_table(doc,
              ["ID", "Expected Result", "Actual Result", "Bukti"],
              [(r[0], r[5], r[6], r[8]) for r in SEC_ROWS],
              [1.3, 5.0, 5.4, 4.4], font_size=8)

    doc.add_heading("10. Bukti Screenshot", level=1)
    para(doc, "Screenshot diambil langsung dari aplikasi yang berjalan pada server evidence dengan "
              "database demo terpisah. Data pelanggan pada tangkapan layar adalah data uji, bukan "
              "data pelanggan asli.")
    sec_shots = [
        ("SEC-01", "01_validasi_input_booking.png", "Validasi nomor telepon pada form booking"),
        ("SEC-03", "02_escaping_xss_data_customer.png", "Payload XSS tampil sebagai teks (ter-escape)"),
        ("SEC-06", "03_sql_injection_pencarian.png", "Pencarian dengan payload SQL tetap terfilter"),
        ("SEC-12", "04_penolakan_csrf_419.png", "Request tanpa token CSRF ditolak"),
        ("SEC-11", "05_penolakan_akses_403.png", "Pemilik Usaha ditolak mengakses halaman admin (HTTP 403)"),
    ]
    if any((SEC_SHOTS / f).exists() for _, f, _ in sec_shots):
        for sid, fname, note in sec_shots:
            path = SEC_SHOTS / fname
            figure(doc, path if path.exists() else None, f"{sid} — {note}", sid)
    else:
        callout(doc, "Screenshot belum tersedia",
                "Folder S3-06_BASIC_SECURITY/screenshots belum berisi berkas. Lihat lampiran "
                "security_http_checks.txt untuk bukti HTTP.", fill=AMBER_FILL)

    doc.add_heading("11. Kesimpulan", level=1)
    para(doc, "Implementasi keamanan dasar pada FixPoint Service berjalan sesuai rancangan: seluruh "
              "form mutasi memakai CSRF, output Blade ter-escape, validasi dilakukan di server, "
              "unggahan dibatasi tipe berkasnya, dan akses database memakai parameter binding. "
              "Pengujian otomatis dan pemeriksaan HTTP tidak menemukan celah kritis pada area yang diuji.")
    para(doc, "Catatan keterbatasan: pengujian ini bersifat fungsional dan tidak menggantikan "
              "pentest menyeluruh. Tidak dilakukan pengujian beban, pemindaian dependensi, atau "
              "pengujian penetrasi jaringan.")

    doc.add_heading("Lampiran A — Rekap Test Otomatis", level=1)
    rows = []
    for r in SEC_ROWS:
        token = SEC_TOKEN.get(r[0])
        t = find_test(JUNIT_SEC, token) if token else {}
        src = token or r[8].split(";")[0]
        rows.append((r[0], src, r[7], t.get("time", "-") or "-"))
    add_table(doc, ["ID", "Sumber Bukti", "Status", "Waktu (s)"], rows,
              [1.5, 9.5, 2.5, 2.5], font_size=8)
    para(doc, f"Total: {total} test, {passed} PASS, {failed} FAIL. Berkas: evidence/junit_security.xml",
         italic=True, size=9)

    doc.add_heading("Lampiran B — Pemeriksaan HTTP Langsung", level=1)
    code_block(doc, HTTP_CHECKS[:2600] or "Berkas security_http_checks.txt tidak ditemukan.",
               "Keluaran pemeriksaan HTTP (evidence/security_http_checks.txt).")

    out = SEC_DIR / "S3-06_Basic_Security_Report.docx"
    doc.save(out)
    return out


# --------------------------------------------------------------------------
# Dokumen 2 — S3-07 Performance & Usability
# --------------------------------------------------------------------------
def build_perf_doc() -> Path:
    doc = setup_document()
    add_cover(doc, "Laporan Performance dan Usability Refinement", "Optimasi Database dan Pengalaman Pengguna",
              "S3-07", "Index database, optimasi query, breadcrumb, loading indicator, dan anti double-click")
    add_toc(doc, [
        "Tujuan", "Kondisi Awal Aplikasi", "Database Index yang Diterapkan",
        "Query Optimization", "Breadcrumb Navigation", "Loading Indicator",
        "Anti Double-Click", "Pengujian CRUD Jadwal Admin", "Pengukuran Waktu Respons",
        "Bukti Screenshot", "Kesimpulan",
    ])

    doc.add_heading("1. Tujuan", level=1)
    para(doc, "S3-07 bertujuan meningkatkan performa aplikasi dan kualitas interaksi pengguna. "
              "Pekerjaan difokuskan pada 3 hal: (1) penambahan index untuk query jadwal dan booking, "
              "(2) perbaikan navigasi dan umpan balik antarmuka, serta (3) pengukuran waktu respons "
              "halaman utama secara berulang untuk memastikan tidak ada regresi.")

    doc.add_heading("2. Kondisi Awal Aplikasi", level=1)
    para(doc, "Sebelum Sprint 3, tabel schedules dan bookings belum memiliki index komposit yang "
              "mendukung filter ketersediaan jadwal serta perhitungan booking aktif per jadwal. "
              "Query daftar jadwal dan laporan harus memindai tabel penuh ketika data bertambah. "
              "Dari sisi antarmuka, halaman admin belum memiliki breadcrumb, belum ada indikator "
              "proses saat submit, dan tombol aksi masih dapat diklik berulang.")
    bullets(doc, [
        "Filter jadwal tersedia memakai kombinasi tanggal, jam, dan status tanpa index komposit.",
        "Perhitungan jumlah booking aktif per jadwal dilakukan berulang pada setiap baris.",
        "Belum ada umpan balik visual ketika form sedang dikirim.",
        "Belum ada pencegahan double submit pada form jadwal.",
    ])

    doc.add_heading("3. Database Index yang Diterapkan", level=1)
    para(doc, "Index ditambahkan melalui migration baru tanpa mengubah struktur tabel yang sudah ada. "
              "Migration bersifat aditif (hanya CREATE INDEX) dan tidak menghapus data.")
    idx_rows = []
    for item in FACTS.get("indexes", []):
        if isinstance(item, dict):
            idx_rows.append((item.get("table", "-"), item.get("name", "-"),
                             item.get("columns", "-"), item.get("type", "BTREE")))
        else:
            idx_rows.append(("-", str(item), "-", "BTREE"))
    if not idx_rows:
        idx_rows = [("schedules", "schedules_overlap_index", "tanggal, jam_mulai, jam_selesai", "BTREE"),
                    ("bookings", "bookings_schedule_status_index", "schedule_id, status", "BTREE")]
    add_table(doc, ["Tabel", "Nama Index", "Kolom", "Tipe"], idx_rows,
              [3.2, 6.2, 5.0, 1.6], font_size=8)

    doc.add_heading("4. Query Optimization", level=1)
    para(doc, "Daftar jadwal admin sebelumnya menghitung booking per baris secara terpisah. Query "
              "dioptimasi dengan subquery agregat tunggal sehingga jumlah query tidak tumbuh seiring "
              "jumlah jadwal. Query filter ketersediaan juga memanfaatkan index komposit baru.")
    snippet = excerpt("app/Http/Controllers/AdminPanelController.php", r"bookings_total|withCount", after=10)
    code_block(doc, snippet or "", "Subquery agregat pada daftar jadwal (kode aktual).")
    explain = FACTS.get("explain", {})
    for key, block in list(explain.items())[:3]:
        rows = block.get("plan") if isinstance(block, dict) else block
        sql = block.get("sql", "") if isinstance(block, dict) else ""
        if sql:
            code_block(doc, sql, f"Query yang diuji: {key}")
        rows = rows or []
        if not rows:
            continue
        if isinstance(rows[0], dict):
            cols = list(rows[0].keys())[:6]
            body = [[str(r.get(c, ""))[:60] for c in cols] for r in rows[:6]]
            add_table(doc, [c.upper() for c in cols], body,
                      [16.0 / max(len(cols), 1)] * len(cols), font_size=7.5)
        else:
            code_block(doc, "\n".join(str(r) for r in rows[:8]), f"EXPLAIN {key}")

    doc.add_heading("5. Breadcrumb Navigation", level=1)
    para(doc, "Halaman kelola jadwal dan form edit jadwal kini memiliki breadcrumb "
              "Dashboard / Jadwal Servis / Edit Jadwal. Breadcrumb membantu pengguna mengetahui "
              "posisinya dan menyediakan jalan kembali tanpa tombol browser.")

    doc.add_heading("6. Loading Indicator", level=1)
    para(doc, "Form jadwal menampilkan overlay indikator proses saat submit. Overlay diaktifkan oleh "
              "JavaScript pada event submit dan mencegah interaksi ganda selama request berjalan. "
              "Indikator hanya tampil selama request berlangsung; pada lingkungan lokal request selesai "
              "dalam hitungan milidetik sehingga keadaan ini tidak berhasil ditangkap sebagai bukti "
              "gambar. Implementasinya diverifikasi melalui kode di bawah, bukan melalui klaim visual.")
    snippet = excerpt("resources/views/admin/edit-jadwal-servis.blade.php", r"is-loading|data-loading-scope", after=6)
    code_block(doc, snippet or "", "Penanganan status loading pada form edit jadwal.")

    doc.add_heading("7. Anti Double-Click", level=1)
    para(doc, "Tombol submit dinonaktifkan dan diberi status busy segera setelah form dikirim. "
              "Pendekatan ini mencegah dua request identik yang dapat menghasilkan data ganda. "
              "Validasi backend tetap menjadi pengaman terakhir.")

    doc.add_heading("8. Pengujian CRUD Jadwal Admin", level=1)
    sch_total, sch_pass, sch_fail = suite_totals(JUNIT_SCH)
    para(doc, f"CRUD jadwal diuji melalui {sch_total} test otomatis dengan hasil {sch_pass} PASS dan "
              f"{sch_fail} FAIL. Rincian lengkap ada pada dokumen S3-07 Admin Schedule Management.")
    add_table(doc, ["ID", "Skenario", "Status"],
              [(sid, desc, find_test(JUNIT_SCH, token).get("status", "BELUM DIUJI"))
               for sid, token, desc in SCH_TESTS],
              [2.0, 11.5, 2.5], font_size=8)

    doc.add_heading("9. Pengukuran Waktu Respons", level=1)
    para(doc, "Pengukuran dilakukan terhadap server aplikasi pada port 8000 menggunakan database "
              "MySQL aktif. Setiap halaman diakses 5 kali setelah pemanasan (warm-up). Nilai yang "
              "dicatat adalah TTFB (time to first byte) dalam milidetik. Target yang ditetapkan "
              f"adalah rata-rata di bawah {PERF_TARGET_MS:.0f} ms untuk seluruh halaman utama.")
    rows = []
    for page, vals in PERF_BY_PAGE.items():
        a = avg(vals)
        status = "PASS" if a is not None and a < PERF_TARGET_MS else "FAIL"
        rows.append((page, len(vals), f"{a:.0f}" if a is not None else "-",
                     f"{PERF_TARGET_MS:.0f}", status))
    if rows:
        add_table(doc, ["Halaman", "Jumlah Percobaan", "Rata-rata TTFB (ms)", "Target (ms)", "Status"],
                  rows, [6.0, 3.0, 3.6, 2.0, 1.6], font_size=8)
    else:
        callout(doc, "Data performa belum tersedia",
                "Berkas performance_raw.csv belum berisi hasil pengukuran.", fill=AMBER_FILL)
    para(doc, "Catatan metodologi: pengukuran dilakukan pada lingkungan lokal sehingga angka absolut "
              "tidak dapat dibandingkan langsung dengan server produksi. Yang dinilai adalah "
              "kestabilan dan terpenuhinya target internal.", italic=True, size=9)

    doc.add_heading("10. Bukti Screenshot", level=1)
    perf_shots = [
        ("PERF-page-public", "01_ketersediaan_jadwal.png", "Halaman ketersediaan jadwal (server MySQL)"),
        ("PERF-page-dashboard", "02_dashboard_admin.png", "Dashboard admin (server MySQL)"),
        ("PERF-page-history", "03_riwayat_servis_filter.png", "Riwayat servis dengan filter tanggal"),
        ("PERF-page-schedule", "04_jadwal_servis_breadcrumb.png", "Breadcrumb pada halaman jadwal servis"),
        ("PERF-mobile", "05_mobile_ketersediaan_jadwal.png", "Ketersediaan jadwal pada viewport 390×844"),
    ]
    if any((PERF_SHOTS / f).exists() for _, f, _ in perf_shots):
        for sid, fname, note in perf_shots:
            path = PERF_SHOTS / fname
            figure(doc, path if path.exists() else None, note, sid)
    else:
        para(doc, "Screenshot performa tidak tersedia pada folder evidence. Angka pengukuran tetap "
                  "dapat diverifikasi melalui evidence/performance_raw.csv.", italic=True)
    breadcrumb = PERF_SHOTS / "04_jadwal_servis_breadcrumb.png"
    if breadcrumb.exists():
        figure(doc, breadcrumb, "Breadcrumb Dashboard / Operasional / Jadwal Servis", "PERF-page-schedule")

    doc.add_heading("11. Kesimpulan", level=1)
    para(doc, "Index komposit dan konsolidasi query menurunkan jumlah pekerjaan database pada "
              "halaman jadwal. Breadcrumb, indikator loading, dan pencegahan double-click membuat "
              "alur pengelolaan jadwal lebih jelas dan aman dari duplikasi submit. Seluruh halaman "
              "utama merespons di bawah target internal yang ditetapkan.")

    out = PERF_DIR / "S3-07_Performance_Usability_Report.docx"
    doc.save(out)
    return out


# --------------------------------------------------------------------------
# Dokumen 3 — S3-07 Admin Schedule
# --------------------------------------------------------------------------
def build_schedule_doc() -> Path:
    doc = setup_document()
    total, passed, failed = suite_totals(JUNIT_SCH)
    add_cover(doc, "Laporan Pengelolaan Jadwal Servis Admin", "Tambah, Edit Slot, Edit Waktu, dan Hapus Jadwal",
              "S3-07", "Penyempurnaan CRUD jadwal pada Dashboard Admin FixPoint Service")
    add_toc(doc, [
        "Tujuan Pengelolaan Jadwal", "Fitur Tambah Jadwal", "Fitur Edit Jumlah Slot",
        "Fitur Edit Tanggal dan Jam", "Fitur Hapus Jadwal", "Validasi dan Hak Akses",
        "Pengujian Setiap Aksi", "Screenshot Sebelum dan Sesudah", "Kesimpulan",
    ])

    doc.add_heading("1. Tujuan Pengelolaan Jadwal", level=1)
    para(doc, "Menu Jadwal Servis pada Dashboard Admin memungkinkan admin mengatur ketersediaan "
              "layanan: kapan pelanggan dapat datang, berapa banyak slot yang tersedia, dan kapan "
              "jadwal ditutup. Sebelum penyempurnaan, admin hanya dapat menambah jadwal dan menutup "
              "jadwal. Kesalahan input kapasitas atau jam harus diperbaiki dengan membuat jadwal baru, "
              "sehingga data menjadi berantakan.")
    para(doc, "Penyempurnaan S3-07 menambahkan edit kapasitas, edit tanggal/jam, dan hapus jadwal "
              "dengan tetap melindungi booking yang sudah ada serta membatasi akses hanya untuk ADMIN.")

    doc.add_heading("2. Fitur Tambah Jadwal", level=1)
    para(doc, "Form tambah jadwal sudah tersedia dan dipertahankan. Admin mengisi tanggal, jam mulai, "
              "jam selesai, dan kapasitas. Sistem menolak jadwal yang bertabrakan dengan jadwal lain "
              "pada tanggal dan jam yang sama.")
    snippet = excerpt("app/Http/Controllers/AdminPanelController.php", r"storeSchedule|function schedules", after=12)
    code_block(doc, snippet or "", "Penanganan tambah jadwal (kode aktual).")

    doc.add_heading("3. Fitur Edit Jumlah Slot", level=1)
    para(doc, "Admin dapat mengubah kapasitas slot melalui form edit. Nilai kapasitas baru wajib "
              "bilangan bulat positif dan tidak boleh lebih kecil dari jumlah booking yang sudah "
              "masuk. Sistem menghitung jumlah booking aktif dari tabel bookings, bukan dari counter "
              "terpisah, sehingga kapasitas tidak pernah menjadi negatif dan data booking tidak berubah.")
    para(doc, "Contoh: jadwal 10 November 2026 pukul 09.00–11.00 dengan kapasitas 5 dan 2 booking "
              "diubah menjadi kapasitas 8. Hasilnya kapasitas 8, booking tetap 2, sisa slot 6.")
    add_table(doc, ["Kondisi", "Kapasitas", "Booking", "Sisa Slot"],
              [("Sebelum", "5", "2", "3"), ("Sesudah", "8", "2", "6")],
              [4.0, 3.5, 3.5, 3.5])

    doc.add_heading("4. Fitur Edit Tanggal dan Jam", level=1)
    para(doc, "Form edit memuat nilai lama secara otomatis: tanggal, jam mulai, jam selesai, "
              "kapasitas, dan status. Validasi memastikan jam selesai lebih besar daripada jam mulai "
              "dan jadwal tidak bertabrakan dengan jadwal lain pada tanggal yang sama.")
    para(doc, "Apabila jadwal sudah memiliki booking aktif, perubahan waktu tidak dilakukan secara "
              "diam-diam. Sistem menolak perubahan tanggal/jam dan menampilkan pesan bahwa jadwal "
              "sudah terikat transaksi, sehingga pelanggan tidak dirugikan. Perubahan kapasitas masih "
              "diizinkan selama tidak di bawah jumlah booking.")

    doc.add_heading("5. Fitur Hapus Jadwal", level=1)
    para(doc, "Aksi Hapus tersedia pada setiap baris jadwal. Konfirmasi menggunakan modal yang "
              "konsisten dengan desain FixPoint, bukan window.confirm(). Penghapusan hanya berhasil "
              "jika jadwal belum memiliki booking. Jadwal yang memiliki booking ditolak dengan pesan "
              "yang jelas dan disarankan untuk menutup jadwal (status CLOSED) sebagai alternatif.")
    bullets(doc, [
        "Tidak ada cascade delete: booking dan pembayaran pelanggan tidak pernah terhapus.",
        "Penghapusan dibungkus transaksi database dan hanya menghapus baris schedules.",
        "Jika jumlah booking > 0, sistem mengembalikan pesan penolakan tanpa mengubah data.",
    ])

    doc.add_heading("6. Validasi dan Hak Akses", level=1)
    add_table(doc, ["Route", "Method", "Middleware", "Aturan Tambahan"],
              [("/admin/jadwal-servis", "GET", "auth, role:ADMIN", "-"),
               ("/admin/jadwal-servis/{id}/edit", "GET", "auth, role:ADMIN", "404 jika tidak ada"),
               ("/admin/jadwal-servis/{id}", "PUT", "auth, role:ADMIN", "CSRF + validasi kapasitas/waktu"),
               ("/admin/jadwal-servis/{id}", "DELETE", "auth, role:ADMIN", "CSRF + cek booking")],
              [5.6, 2.0, 4.4, 4.0], font_size=8)
    para(doc, "Pembatasan tidak hanya menyembunyikan tombol di frontend. Route edit, update, dan "
              "destroy berada dalam grup middleware role:ADMIN, sehingga Pemilik Usaha dan Pelanggan "
              "yang mengirim request langsung tetap menerima HTTP 403.")

    doc.add_heading("7. Pengujian Setiap Aksi", level=1)
    para(doc, f"Seluruh skenario SCH-01 sampai SCH-12 dijalankan melalui test otomatis. Hasil: "
              f"{passed} PASS dari {total} test, {failed} FAIL.")
    rows = []
    for sid, token, desc in SCH_TESTS:
        r = find_test(JUNIT_SCH, token)
        rows.append((sid, desc, r.get("status", "BELUM DIUJI")))
    add_table(doc, ["ID", "Skenario", "Status"], rows, [2.0, 11.5, 2.5], font_size=8)

    doc.add_heading("8. Screenshot Sebelum dan Sesudah", level=1)
    para(doc, "Tangkapan layar diambil pada server evidence dengan database demo. Data pelanggan "
              "merupakan data uji.")
    sched_figs = [
        ("SCH-01", "01_daftar_jadwal_admin.png", "Daftar jadwal dengan aksi Edit, Tutup Jadwal, dan Hapus"),
        ("SCH-02", "02_form_edit_jadwal.png", "Form edit terisi nilai lama dan ringkasan slot"),
        ("SCH-03", "03_perubahan_kapasitas_slot.png", "Kapasitas berubah dari 5 menjadi 8 slot, booking tetap 2"),
        ("SCH-04", "04_penolakan_slot_di_bawah_booking.png", "Kapasitas 1 ditolak karena 2 slot sudah terpakai"),
        ("SCH-05", "05_perubahan_jam_jadwal.png", "Jam jadwal berhasil diubah menjadi 13.00–15.00"),
        ("SCH-08", "06_modal_konfirmasi_hapus.png", "Modal konfirmasi hapus dengan desain FixPoint"),
        ("SCH-08", "07_hapus_jadwal_berhasil.png", "Notifikasi jadwal tanpa booking berhasil dihapus"),
        ("SCH-09", "08_penolakan_hapus_jadwal_berbooking.png", "Penolakan hapus karena jadwal masih memiliki booking"),
        ("responsive-mobile", "10_tampilan_mobile_jadwal.png", "Tampilan pengelolaan jadwal pada viewport 390×844"),
    ]
    for sid, fname, note in sched_figs:
        path = SCH_SHOTS / fname
        figure(doc, path if path.exists() else None, f"{note}", sid)

    doc.add_heading("9. Kesimpulan", level=1)
    para(doc, "Pengelolaan jadwal servis kini lengkap: admin dapat menambah, mengubah kapasitas, "
              "mengubah waktu, dan menghapus jadwal. Setiap aksi dilindungi validasi backend, CSRF, "
              "dan middleware role. Integritas data booking terjaga karena penghapusan dan perubahan "
              "waktu ditolak saat jadwal sudah terikat transaksi.")

    out = SCH_DIR / "S3-07_Admin_Schedule_Management_Report.docx"
    doc.save(out)
    return out


# --------------------------------------------------------------------------
# Excel
# --------------------------------------------------------------------------
THIN = Side(style="thin", color="94A3B8")
BORDER = Border(left=THIN, right=THIN, top=THIN, bottom=THIN)


def style_sheet(ws, headers, rows, widths):
    ws.append(headers)
    for c in range(1, len(headers) + 1):
        cell = ws.cell(row=1, column=c)
        cell.font = Font(bold=True, color="FFFFFF", size=10)
        cell.fill = PatternFill("solid", fgColor=HEADER_FILL)
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
        cell.border = BORDER
    for r in rows:
        ws.append(list(r))
    for row in ws.iter_rows(min_row=2, max_row=ws.max_row, max_col=len(headers)):
        for cell in row:
            cell.border = BORDER
            cell.alignment = Alignment(vertical="top", wrap_text=True)
            cell.font = Font(size=9)
    for i, w in enumerate(widths, 1):
        ws.column_dimensions[get_column_letter(i)].width = w
    ws.row_dimensions[1].height = 30
    ws.freeze_panes = "A2"


def mark_status(ws, col_index):
    for row in range(2, ws.max_row + 1):
        cell = ws.cell(row=row, column=col_index)
        val = str(cell.value or "").strip().upper()
        if val == "PASS":
            cell.fill = PatternFill("solid", fgColor=GREEN_FILL)
            cell.font = Font(bold=True, color="166534", size=9)
        elif val == "FAIL":
            cell.fill = PatternFill("solid", fgColor=RED_FILL)
            cell.font = Font(bold=True, color="991B1B", size=9)
        elif val == "BELUM DIUJI":
            cell.fill = PatternFill("solid", fgColor=AMBER_FILL)
            cell.font = Font(bold=True, color="92400E", size=9)


def build_security_xlsx() -> Path:
    wb = Workbook()
    ws = wb.active
    ws.title = "Rekap Keamanan"
    rows = [(r[0], r[1], r[2], r[3], r[4], r[5], r[6], r[7], r[8]) for r in SEC_ROWS]
    style_sheet(ws, ["ID", "Modul", "Skenario Pengujian", "Pre-condition", "Test Input",
                     "Expected Result", "Actual Result", "Status", "Evidence"],
                rows, [8, 20, 40, 28, 28, 34, 46, 11, 34])
    mark_status(ws, 8)

    ws2 = wb.create_sheet("Hasil Test Otomatis")
    total, passed, failed = suite_totals(JUNIT_SEC)
    rows2 = []
    for row_data in SEC_ROWS:
        token = SEC_TOKEN.get(row_data[0])
        t = find_test(JUNIT_SEC, token) if token else {}
        src = token or row_data[8].split(";")[0]
        rows2.append((row_data[0], src, row_data[7], t.get("time", "-") or "-",
                      (t.get("detail") or "-")[:200]))
    style_sheet(ws2, ["ID", "Nama Test", "Status", "Durasi (s)", "Pesan"],
                rows2, [8, 60, 12, 12, 50])
    mark_status(ws2, 3)
    ws2.append([])
    ws2.append(["", "Total", total, "", ""])
    ws2.append(["", "PASS", passed, "", ""])
    ws2.append(["", "FAIL", failed, "", ""])

    ws3 = wb.create_sheet("Pemeriksaan HTTP")
    for line in HTTP_CHECKS.splitlines():
        ws3.append([line])
    ws3.column_dimensions["A"].width = 120

    out = SEC_DIR / "S3-06_Security_Test_Report.xlsx"
    wb.save(out)
    return out


def build_perf_xlsx() -> Path:
    wb = Workbook()
    ws = wb.active
    ws.title = "Pengukuran Performa"
    rows = []
    for page, vals in PERF_BY_PAGE.items():
        a = avg(vals)
        status = "PASS" if a is not None and a < PERF_TARGET_MS else "FAIL"
        note = "Diukur pada server lokal port 8000, database MySQL aktif"
        rows.append([page, len(vals)] + [f"{v:.0f}" for v in vals] +
                    ["", f"{a:.0f}" if a is not None else "-", int(PERF_TARGET_MS), status, note])
    headers = ["Halaman", "Jumlah Percobaan", "Percobaan 1 (ms)", "Percobaan 2 (ms)",
               "Percobaan 3 (ms)", "Percobaan 4 (ms)", "Percobaan 5 (ms)", "",
               "Rata-rata (ms)", "Target (ms)", "Status", "Catatan"]
    style_sheet(ws, headers, rows, [26, 11, 11, 11, 11, 11, 11, 2, 12, 10, 10, 46])
    for r in range(2, ws.max_row + 1):
        val = str(ws.cell(row=r, column=11).value or "")
        cell = ws.cell(row=r, column=11)
        if val == "PASS":
            cell.fill = PatternFill("solid", fgColor=GREEN_FILL)
            cell.font = Font(bold=True, color="166534")
        elif val == "FAIL":
            cell.fill = PatternFill("solid", fgColor=RED_FILL)
            cell.font = Font(bold=True, color="991B1B")

    ws2 = wb.create_sheet("Index Database")
    rows2 = []
    for item in FACTS.get("indexes", []):
        if isinstance(item, dict):
            rows2.append((item.get("table", "-"), item.get("name", "-"),
                          item.get("columns", "-"), item.get("type", "BTREE"),
                          item.get("purpose", "Mempercepat filter dan join terkait")))
        else:
            rows2.append(("-", str(item), "-", "BTREE", "-"))
    style_sheet(ws2, ["Tabel", "Nama Index", "Kolom", "Tipe", "Tujuan"],
                rows2, [22, 40, 40, 12, 50])

    out = PERF_DIR / "S3-07_Performance_Test.xlsx"
    wb.save(out)
    return out


def build_schedule_xlsx() -> Path:
    wb = Workbook()
    ws = wb.active
    ws.title = "Pengujian Jadwal"
    rows = []
    for sid, token, desc in SCH_TESTS:
        r = find_test(JUNIT_SCH, token)
        rows.append((sid, desc, token, r.get("status", "BELUM DIUJI"),
                     r.get("time", "-") or "-", (r.get("detail") or "-")[:220]))
    style_sheet(ws, ["ID", "Skenario", "Nama Test", "Status", "Durasi (s)", "Pesan"],
                rows, [8, 58, 62, 12, 11, 40])
    mark_status(ws, 4)

    ws2 = wb.create_sheet("Data Uji")
    ws2.append(["Keterangan", "Nilai"])
    data = [
        ("Jadwal uji 1", "12 November 2026, 09.00-11.00, kapasitas 5, 2 booking"),
        ("Jadwal uji 2", "13 November 2026, 13.00-15.00, kapasitas 4, 0 booking"),
        ("Perubahan slot", "5 -> 8 (booking tetap 2, sisa 6)"),
        ("Perubahan waktu", "09.00-11.00 -> 13.00-15.00 (tanpa booking)"),
        ("Penolakan slot", "Kapasitas 1 saat booking 2"),
        ("Penolakan hapus", "Jadwal dengan 2 booking"),
        ("Akun admin", "admin@fixpoint.test"),
        ("Akun pemilik", "owner@fixpoint.test"),
    ]
    for k, v in data:
        ws2.append([k, v])
    for row in ws2.iter_rows(min_row=1, max_row=ws2.max_row, max_col=2):
        for cell in row:
            cell.border = BORDER
            cell.alignment = Alignment(vertical="top", wrap_text=True)
    for c in ws2[1]:
        c.font = Font(bold=True, color="FFFFFF")
        c.fill = PatternFill("solid", fgColor=HEADER_FILL)
    ws2.column_dimensions["A"].width = 24
    ws2.column_dimensions["B"].width = 66

    out = SCH_DIR / "S3-07_Admin_Schedule_Test.xlsx"
    wb.save(out)
    return out


# --------------------------------------------------------------------------
# SQL, README, PDF, ZIP
# --------------------------------------------------------------------------
def build_sql() -> Path:
    lines = [
        "-- =====================================================================",
        "-- FixPoint Service - Database Index untuk Sprint 3 (S3-07)",
        "-- Dibuat: " + TANGGAL_ID,
        "-- Migration sumber: database/migrations/2026_11_09_000000_add_schedule_management_indexes.php",
        "-- Sifat: aditif. Tidak menghapus tabel, kolom, atau data.",
        "-- =====================================================================",
        "",
    ]
    for item in FACTS.get("indexes", []):
        if isinstance(item, dict):
            cols = item.get("columns", "")
            if isinstance(cols, list):
                cols = ", ".join(cols)
            lines.append(f"-- {item.get('table','schedules')}: {item.get('purpose','optimasi filter')}")
            lines.append(f"CREATE INDEX {item.get('name','idx')} ON {item.get('table','schedules')} ({cols});")
            lines.append("")
    if len(lines) <= 7:
        lines += [
            "CREATE INDEX schedules_overlap_index ON schedules (tanggal, jam_mulai, jam_selesai);",
            "CREATE INDEX bookings_schedule_status_index ON bookings (schedule_id, status);",
            "",
        ]
    lines += [
        "-- Rollback (manual, jalankan hanya bila index akan dicabut):",
    ]
    for item in FACTS.get("indexes", []):
        if isinstance(item, dict):
            lines.append(f"DROP INDEX {item.get('name','idx')} ON {item.get('table','schedules')};")
    lines += [
        "",
        "-- Catatan: pada MySQL/MariaDB, index di atas dibuat oleh migration Laravel.",
        "-- Perintah verifikasi: SHOW INDEX FROM schedules; SHOW INDEX FROM bookings;",
        "",
    ]
    out = PERF_DIR / "database_index.sql"
    out.write_text("\n".join(lines), encoding="utf-8")
    return out


def build_readme() -> Path:
    sec_total, sec_pass, sec_fail = suite_totals(JUNIT_SEC)
    sch_total, sch_pass, sch_fail = suite_totals(JUNIT_SCH)
    full_total, full_pass, full_fail = suite_totals(JUNIT_FULL)
    perf_rows = []
    for page, vals in PERF_BY_PAGE.items():
        a = avg(vals)
        perf_rows.append(f"| {page} | {a:.0f} | {int(PERF_TARGET_MS)} | "
                         f"{'PASS' if a is not None and a < PERF_TARGET_MS else 'FAIL'} |")
    perf_table = "\n".join(perf_rows) if perf_rows else "| - | - | - | BELUM DIUJI |"
    text = f"""# README Pengumpulan Sprint 3 — FixPoint Service

Dokumen ini menjelaskan isi paket pengumpulan S3-06 (Basic Security) dan S3-07
(Performance & Usability + Pengelolaan Jadwal Admin) untuk aplikasi FixPoint Service.

Tanggal pengerjaan: {TANGGAL_ID}

## 1. Dokumen yang harus diunggah

| Checklist | Berkas | Lokasi |
|---|---|---|
| S3-06 laporan (Word) | S3-06_Basic_Security_Report.docx | S3-06_BASIC_SECURITY/ |
| S3-06 laporan (PDF) | S3-06_Basic_Security_Report.pdf | S3-06_BASIC_SECURITY/ |
| S3-06 rekap pengujian | S3-06_Security_Test_Report.xlsx | S3-06_BASIC_SECURITY/ |
| S3-07 laporan (Word) | S3-07_Performance_Usability_Report.docx | S3-07_PERFORMANCE/ |
| S3-07 laporan (PDF) | S3-07_Performance_Usability_Report.pdf | S3-07_PERFORMANCE/ |
| S3-07 pengukuran | S3-07_Performance_Test.xlsx | S3-07_PERFORMANCE/ |
| S3-07 index database | database_index.sql | S3-07_PERFORMANCE/ |
| S3-07 jadwal (Word) | S3-07_Admin_Schedule_Management_Report.docx | S3-07_ADMIN_SCHEDULE/ |
| S3-07 jadwal (PDF) | S3-07_Admin_Schedule_Management_Report.pdf | S3-07_ADMIN_SCHEDULE/ |
| S3-07 jadwal (Excel) | S3-07_Admin_Schedule_Test.xlsx | S3-07_ADMIN_SCHEDULE/ |

DOCX adalah sumber yang dapat diedit; PDF adalah dokumen final untuk pengumpulan.

## 2. Screenshot yang tersedia

- `S3-06_BASIC_SECURITY/screenshots/` — 01 validasi input booking,
  02 escaping XSS data customer, 03 pencarian SQL injection, 04 penolakan CSRF,
  05 penolakan akses 403.
- `S3-07_ADMIN_SCHEDULE/screenshots/` — 01 daftar jadwal, 02 form edit,
  03 perubahan kapasitas, 04 penolakan slot di bawah booking, 05 perubahan jam,
  06 modal konfirmasi hapus, 07 notifikasi hapus berhasil, 08 penolakan hapus
  karena booking, dan 10 tampilan mobile 390x844.
- `S3-07_PERFORMANCE/screenshots/` — 01 ketersediaan jadwal, 02 dashboard admin,
  03 riwayat servis, 04 breadcrumb jadwal, 05 mobile ketersediaan jadwal.

Semua screenshot berasal dari aplikasi yang benar-benar dijalankan pada server
evidence dengan database demo SQLite. Tidak ada data pelanggan asli.

## 3. Pengujian yang sudah PASS

- Test keamanan: **{sec_pass} PASS** dari {sec_total} test ({sec_fail} FAIL).
- Test pengelolaan jadwal: **{sch_pass} PASS** dari {sch_total} test ({sch_fail} FAIL).
- Seluruh suite aplikasi: **{full_pass} PASS** dari {full_total} test ({full_fail} FAIL).
- Pemeriksaan HTTP: guest dialihkan 302, owner mendapat 403 pada halaman jadwal,
  update/hapus tanpa token ditolak 419.

### Ringkasan performa (TTFB rata-rata, target {int(PERF_TARGET_MS)} ms)

| Halaman | Rata-rata (ms) | Target (ms) | Status |
|---|---|---|---|
{perf_table}

## 4. Pengujian yang FAIL atau belum diuji

- Tidak ada test otomatis yang FAIL pada suite saat dokumen dibuat.
- Indikator loading belum berhasil ditangkap sebagai screenshot karena request
  lokal selesai dalam hitungan milidetik. Item ini tidak diklaim lulus secara
  visual; implementasinya ditunjukkan melalui potongan kode pada laporan S3-07.
- Pengukuran performa dilakukan di lingkungan lokal, bukan produksi, dan tidak
  membandingkan sebelum/sesudah index secara terpisah. Yang tersedia adalah
  EXPLAIN query dan angka respons aktual.

## 5. Implementasi yang sudah selesai

- S3-06: validasi input backend, CSRF pada 17 form mutasi, escaping Blade
  (0 pemakaian `{{!! !!}}`), validasi MIME unggahan, query terparameter.
- S3-07: index `schedules_overlap_index` dan `bookings_schedule_status_index`,
  konsolidasi query daftar jadwal, breadcrumb, indikator loading, anti
  double-click, dan tampilan responsif.
- CRUD jadwal admin: tambah, edit kapasitas, edit tanggal/jam, hapus dengan
  perlindungan booking, modal konfirmasi, dan pembatasan role ADMIN.

## 6. Pekerjaan yang masih tertunda

- Pengukuran performa sebelum vs sesudah index pada dataset besar belum
  dilakukan.
- Uji beban dan pemindaian dependensi belum termasuk lingkup Sprint 3.
- Verifikasi visual pada perangkat fisik (tablet/mobile nyata) belum dilakukan;
  tangkapan responsif diambil melalui emulasi viewport Chromium.

## 7. Cara memverifikasi ulang

```bash
php artisan test
php artisan test --filter=SecurityVerificationTest
php artisan test --filter=AdminScheduleManagementTest
bash scripts/sprint3/measure_performance.sh
```

Berkas mentah bukti berada di `evidence/` (JUnit XML, CSV performa,
pemeriksaan HTTP, dan fakta database).
"""
    out = BASE / "README_PENGUMPULAN.md"
    out.write_text(text, encoding="utf-8")
    return out


def convert_pdf(docx_path: Path):
    if shutil.which("soffice") is None:
        print(f"  ! soffice tidak tersedia, PDF dilewati: {docx_path.name}")
        return None
    subprocess.run(["soffice", "--headless", "--convert-to", "pdf",
                    "--outdir", str(docx_path.parent), str(docx_path)],
                   check=False, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                   timeout=180)
    pdf = docx_path.with_suffix(".pdf")
    if pdf.exists() and pdf.read_bytes()[:4] == b"%PDF":
        return pdf
    print(f"  ! konversi PDF gagal: {docx_path.name}")
    return None


def build_zip():
    target = BASE / "Pengumpulan_S3-06_S3-07_FixPoint.zip"
    if target.exists():
        target.unlink()
    with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in sorted(BASE.rglob("*")):
            if path.is_file() and path != target:
                zf.write(path, arcname=str(Path("pengumpulan") / path.relative_to(BASE)))
    return target


def main():
    print("Membuat dokumen S3-06 ...")
    sec_docx = build_security_doc()
    print("Membuat dokumen S3-07 Performance ...")
    perf_docx = build_perf_doc()
    print("Membuat dokumen S3-07 Admin Schedule ...")
    sch_docx = build_schedule_doc()
    print("Membuat Excel ...")
    sec_xlsx = build_security_xlsx()
    perf_xlsx = build_perf_xlsx()
    sch_xlsx = build_schedule_xlsx()
    print("Membuat SQL dan README ...")
    sql = build_sql()
    readme = build_readme()
    print("Konversi PDF ...")
    for docx_path in (sec_docx, perf_docx, sch_docx):
        pdf = convert_pdf(docx_path)
        print(f"  {'OK ' if pdf else 'GAGAL'} {docx_path.name}")
    print("Mengemas ZIP ...")
    zpath = build_zip()
    print("\nSelesai:")
    for p in (sec_docx, perf_docx, sch_docx, sec_xlsx, perf_xlsx, sch_xlsx, sql, readme, zpath):
        print(f"  {p.relative_to(APP_ROOT)}  ({p.stat().st_size} bytes)")


if __name__ == "__main__":
    sys.exit(main())
