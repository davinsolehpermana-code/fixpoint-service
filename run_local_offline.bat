@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul 2>&1
title FixPoint Service - Mode Offline LAN

REM =====================================================================
REM  run_local_offline.bat
REM  Menjalankan FixPoint Service tanpa internet memakai server lokal,
REM  dapat diakses perangkat lain pada jaringan WiFi/hotspot yang sama.
REM  Jalankan dengan klik dua kali, atau dari cmd di folder proyek.
REM =====================================================================

cd /d "%~dp0"

echo ============================================================
echo   FIXPOINT SERVICE - MENJALANKAN APLIKASI LOKAL (OFFLINE LAN)
echo ============================================================
echo.

REM ---------------------------------------------------------------------
REM 1. Periksa PHP
REM ---------------------------------------------------------------------
set "PHP_EXE="
where php >nul 2>&1
if %errorlevel%==0 (
    for /f "delims=" %%p in ('where php') do if not defined PHP_EXE set "PHP_EXE=%%p"
)
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"

if not defined PHP_EXE (
    echo [GAGAL] PHP tidak ditemukan.
    echo         Pasang PHP 8.3 atau lebih baru, atau pastikan folder php
    echo         sudah masuk PATH. Jika memakai XAMPP, pastikan C:\xampp\php ada.
    pause
    exit /b 1
)
echo [OK] PHP     : %PHP_EXE%
"%PHP_EXE%" -r "echo '     versi  : ' . PHP_VERSION . PHP_EOL;"

REM ---------------------------------------------------------------------
REM 2. Pastikan MySQL lokal berjalan
REM ---------------------------------------------------------------------
echo.
echo [..] Memeriksa MySQL lokal pada port 3306 ...
netstat -an | findstr /R /C:"127.0.0.1:3306 .*LISTENING" >nul 2>&1
if %errorlevel%==0 (
    echo [OK] MySQL sudah berjalan.
) else (
    echo [..] MySQL belum berjalan, mencoba menyalakan ...
    net start mysql >nul 2>&1
    if %errorlevel%==0 (
        echo [OK] Service MySQL dinyalakan.
    ) else (
        if exist "C:\xampp\mysql\bin\mysqld.exe" (
            start "MySQL FixPoint" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
            echo [..] Menunggu MySQL siap ...
            set /a MYSQL_WAIT=0
            :wait_mysql
            timeout /t 2 /nobreak >nul
            netstat -an | findstr /R /C:"127.0.0.1:3306 .*LISTENING" >nul 2>&1
            if %errorlevel%==0 goto mysql_ready
            set /a MYSQL_WAIT+=1
            if !MYSQL_WAIT! LSS 15 goto wait_mysql
            echo [GAGAL] MySQL tidak merespons pada port 3306.
            echo         Nyalakan MySQL lewat XAMPP Control Panel, lalu jalankan ulang skrip ini.
            pause
            exit /b 1
            :mysql_ready
            echo [OK] MySQL siap.
        ) else (
            echo [GAGAL] MySQL tidak berjalan dan mysqld.exe tidak ditemukan.
            echo         Nyalakan MySQL lewat XAMPP Control Panel, lalu jalankan ulang skrip ini.
            pause
            exit /b 1
        )
    )
)

REM ---------------------------------------------------------------------
REM 3. Periksa konfigurasi .env dan database
REM ---------------------------------------------------------------------
echo.
if not exist ".env" (
    echo [GAGAL] File .env tidak ditemukan.
    echo         Salin .env.example menjadi .env lalu isi APP_KEY dan data MySQL.
    pause
    exit /b 1
)
echo [OK] File .env ditemukan.

"%PHP_EXE%" artisan --version >nul 2>&1
if not %errorlevel%==0 (
    echo [GAGAL] artisan tidak dapat dijalankan. Periksa isi .env dan folder vendor.
    pause
    exit /b 1
)

"%PHP_EXE%" -r "exit(str_contains((string) file_get_contents('.env'), 'APP_KEY=base64:') ? 0 : 1);"
if not %errorlevel%==0 (
    echo [..] APP_KEY belum diisi, membuat kunci aplikasi ...
    "%PHP_EXE%" artisan key:generate --force
)
echo [OK] APP_KEY tersedia.

"%PHP_EXE%" artisan migrate:status >nul 2>&1
if not %errorlevel%==0 (
    echo [GAGAL] Database tidak dapat dihubungi atau tabel belum dibuat.
    echo         Periksa DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD pada .env,
    echo         lalu jalankan: php artisan migrate --seed
    pause
    exit /b 1
)
echo [OK] Database lokal terhubung.

REM ---------------------------------------------------------------------
REM 4. Bersihkan cache yang dapat mengganggu mode lokal
REM ---------------------------------------------------------------------
echo.
echo [..] Membersihkan cache konfigurasi, route, dan view ...
"%PHP_EXE%" artisan optimize:clear >nul 2>&1
echo [OK] Cache dibersihkan.

REM ---------------------------------------------------------------------
REM 5. Pastikan storage siap (folder unggahan bukti pembayaran)
REM ---------------------------------------------------------------------
if not exist "storage\app\public\payment_proofs" (
    mkdir "storage\app\public\payment_proofs" >nul 2>&1
)
if not exist "public\storage" (
    "%PHP_EXE%" artisan storage:link >nul 2>&1
)
echo [OK] Folder storage siap.

REM ---------------------------------------------------------------------
REM 6. Tampilkan alamat IP LAN yang sebenarnya
REM ---------------------------------------------------------------------
echo.
echo ============================================================
echo   ALAMAT AKSES APLIKASI
echo ============================================================
echo   Komputer ini   : http://127.0.0.1:8000
set "LAN_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /C:"IPv4 Address" /C:"IPv4 Address." /C:"Alamat IPv4"') do (
    set "CANDIDATE=%%a"
    set "CANDIDATE=!CANDIDATE: =!"
    if not defined LAN_IP if not "!CANDIDATE!"=="127.0.0.1" set "LAN_IP=!CANDIDATE!"
)
if defined LAN_IP (
    echo   Perangkat lain: http://!LAN_IP!:8000
) else (
    echo   Perangkat lain: jalankan "ipconfig" lalu pakai alamat IPv4
    echo                   adapter WiFi/hotspot yang aktif, port 8000.
)
echo ============================================================
echo   Tekan CTRL+C pada jendela ini untuk menghentikan server.
echo ============================================================
echo.

REM ---------------------------------------------------------------------
REM 7. Jalankan server Laravel untuk seluruh antarmuka jaringan
REM ---------------------------------------------------------------------
"%PHP_EXE%" artisan serve --host 0.0.0.0 --port 8000

echo.
echo Server dihentikan.
pause
endlocal
