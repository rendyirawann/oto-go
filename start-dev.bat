@echo off
setlocal EnableExtensions
title OtoGo - Launcher

rem ===========================================================================
rem  OtoGo - jalankan seluruh lingkungan pengembangan dari SATU terminal.
rem
rem    Web + API + Swagger : satu "php artisan serve"  -> http://127.0.0.1:8822
rem    Queue worker        : php artisan queue:work (Redis)
rem    Reverb (realtime)   : php artisan reverb:start  -> ws://127.0.0.1:8080
rem    Log                 : storage\logs\laravel.log secara real-time
rem    Mobile (Flutter web): http://127.0.0.1:5180   <- aplikasi mobile di browser
rem
rem  Pemakaian:
rem    start-dev.bat           -> satu jendela Windows Terminal, satu tab per service
rem    start-dev.bat single    -> semua service di jendela cmd INI (output berwarna
rem                               per service, Ctrl+C menghentikan semuanya)
rem ===========================================================================

set "PROJ=%~dp0"
set "PROJ=%PROJ:~0,-1%"
set "API_DIR=%PROJ%"
set "MOBILE_DIR=%PROJ%\mobile"

set "API_HOST=127.0.0.1"
set "API_PORT=8822"
set "WEB_HOST=127.0.0.1"
set "WEB_PORT=5180"
set "DB_PORT=5433"
set "REDIS_PORT=6379"
set "REVERB_PORT=8080"

set "LOG=%API_DIR%\storage\logs\laravel.log"
set "MODE=%~1"

echo.
echo  ==========================================================
echo   OtoGo - lingkungan pengembangan
echo  ==========================================================
echo.

rem --- Prasyarat ----------------------------------------------------------
set "GAGAL=0"
set "ADA_MOBILE=1"

if not exist "%API_DIR%\artisan" (
    echo  [X] Folder Laravel tidak ditemukan: %API_DIR%
    set "GAGAL=1"
) else (
    echo  [OK] Laravel       : %API_DIR%
)

if not exist "%API_DIR%\.env" (
    echo  [X] Berkas .env belum ada. Salin dari .env.example lalu isi.
    set "GAGAL=1"
)

where php >nul 2>nul
if errorlevel 1 (
    echo  [X] php tidak ada di PATH. Butuh PHP 8.3+.
    set "GAGAL=1"
) else (
    echo  [OK] PHP           : terpasang
)

if not exist "%MOBILE_DIR%\pubspec.yaml" (
    echo  [!] Folder mobile tidak ditemukan - tab Mobile dilewati.
    set "ADA_MOBILE=0"
)

set "SDK="
for /f "delims=" %%F in ('where flutter 2^>nul') do if not defined SDK set "SDK=%%~dpF"

if "%ADA_MOBILE%"=="1" (
    if not defined SDK (
        echo  [!] Flutter tidak ada di PATH - tab Mobile dilewati.
        set "ADA_MOBILE=0"
    ) else if not exist "%SDK%flutter.bat" (
        echo  [!] SDK Flutter tidak terjangkau di %SDK% - tab Mobile dilewati.
        set "ADA_MOBILE=0"
    ) else (
        echo  [OK] Flutter       : %SDK%
    )
)
set "FLUTTER=%SDK%flutter.bat"

netstat -ano | findstr /R /C:":%DB_PORT% .*LISTENING" >nul 2>nul
if errorlevel 1 (
    echo  [X] PostgreSQL TIDAK mendengarkan di port %DB_PORT%. Nyalakan dulu servicenya.
    set "GAGAL=1"
) else (
    echo  [OK] PostgreSQL    : aktif di port %DB_PORT%
)

netstat -ano | findstr /R /C:":%REDIS_PORT% .*LISTENING" >nul 2>nul
if errorlevel 1 (
    echo  [X] Redis TIDAK mendengarkan di port %REDIS_PORT%. Cache, queue dan rate limit butuh Redis.
    set "GAGAL=1"
) else (
    echo  [OK] Redis         : aktif di port %REDIS_PORT%
)

if "%GAGAL%"=="1" (
    echo.
    echo  Ada prasyarat yang belum terpenuhi. Perbaiki yang bertanda [X] lalu jalankan ulang.
    echo.
    pause
    exit /b 1
)

netstat -ano | findstr /R /C:":%API_PORT% .*LISTENING" >nul 2>nul
if not errorlevel 1 echo  [*] Port %API_PORT% sudah dipakai - server dari sesi sebelumnya mungkin masih hidup.

rem --- Dependensi & database ----------------------------------------------
pushd "%API_DIR%"
if not exist "vendor\autoload.php" (
    echo.
    echo  [*] vendor belum ada. Menjalankan composer install...
    call composer install --no-interaction
)
if not exist "node_modules\.bin\concurrently.cmd" (
    echo.
    echo  [*] node_modules belum ada. Menjalankan npm install...
    call npm install --no-audit --no-fund
)
echo.
echo  [*] Menyiapkan database (migrate)...
call php artisan migrate --force
if not exist "public\storage" call php artisan storage:link >nul 2>nul
call php artisan config:clear >nul 2>nul
popd

if "%ADA_MOBILE%"=="1" (
    if not exist "%MOBILE_DIR%\.dart_tool\package_config.json" (
        echo  [*] Menjalankan flutter pub get...
        pushd "%MOBILE_DIR%"
        call "%FLUTTER%" pub get
        popd
    )
)

if not exist "%API_DIR%\storage\logs" mkdir "%API_DIR%\storage\logs" >nul 2>nul
if not exist "%LOG%" type nul > "%LOG%"

echo.
echo   Web       : http://%API_HOST%:%API_PORT%
echo   Portal    : http://%API_HOST%:%API_PORT%/admin/login
echo   API       : http://%API_HOST%:%API_PORT%/api/health
echo   Swagger   : http://%API_HOST%:%API_PORT%/api/documentation
if "%ADA_MOBILE%"=="1" echo   Mobile    : http://%WEB_HOST%:%WEB_PORT%   ^<- tunggu sampai "is being served at"
echo.

set "CMD_WEB=php artisan serve --host=%API_HOST% --port=%API_PORT%"
set "CMD_QUEUE=php artisan queue:work --tries=3 --timeout=90 --sleep=1"
set "CMD_REVERB=php artisan reverb:start --host=127.0.0.1 --port=%REVERB_PORT%"
set "CMD_MOBILE=%FLUTTER% run -d web-server --web-hostname %WEB_HOST% --web-port %WEB_PORT% --dart-define=API_BASE_URL=http://%API_HOST%:%API_PORT%"

if /I "%MODE%"=="single" goto :single

where wt >nul 2>nul
if errorlevel 1 goto :fallback

rem --- Satu jendela Windows Terminal, satu tab per service -----------------
rem (wt memakai ";" sebagai pemisah tab, jadi perintah di sini tanpa ";")
if "%ADA_MOBILE%"=="1" (
    wt -w new new-tab --title Web-API -d "%API_DIR%" cmd /k %CMD_WEB% ; new-tab --title Queue -d "%API_DIR%" cmd /k %CMD_QUEUE% ; new-tab --title Reverb -d "%API_DIR%" cmd /k %CMD_REVERB% ; new-tab --title Log -d "%API_DIR%" powershell -NoProfile -NoExit -Command "Get-Content -LiteralPath '%LOG%' -Wait -Tail 30" ; new-tab --title Mobile -d "%MOBILE_DIR%" cmd /k "%FLUTTER%" run -d web-server --web-hostname %WEB_HOST% --web-port %WEB_PORT% --dart-define=API_BASE_URL=http://%API_HOST%:%API_PORT%
) else (
    wt -w new new-tab --title Web-API -d "%API_DIR%" cmd /k %CMD_WEB% ; new-tab --title Queue -d "%API_DIR%" cmd /k %CMD_QUEUE% ; new-tab --title Reverb -d "%API_DIR%" cmd /k %CMD_REVERB% ; new-tab --title Log -d "%API_DIR%" powershell -NoProfile -NoExit -Command "Get-Content -LiteralPath '%LOG%' -Wait -Tail 30"
)
goto :done

:single
rem --- Semua service di jendela ini (concurrently) ---------------------------
echo  Mode single: semua service berjalan di jendela ini. Tekan Ctrl+C untuk berhenti.
echo.
pushd "%API_DIR%"
if "%ADA_MOBILE%"=="1" (
    call node_modules\.bin\concurrently.cmd --kill-others-on-fail -c "#ff5a1f,#9ca3af,#60a5fa,#22c55e" -n web,queue,reverb,mobile "%CMD_WEB%" "%CMD_QUEUE%" "%CMD_REVERB%" "cd /d %MOBILE_DIR% && %CMD_MOBILE%"
) else (
    call node_modules\.bin\concurrently.cmd --kill-others-on-fail -c "#ff5a1f,#9ca3af,#60a5fa" -n web,queue,reverb "%CMD_WEB%" "%CMD_QUEUE%" "%CMD_REVERB%"
)
popd
exit /b 0

:fallback
rem --- Tanpa Windows Terminal: jendela terpisah --------------------------------
start "OtoGo - Web+API %API_PORT%" cmd /k "cd /d "%API_DIR%" && %CMD_WEB%"
start "OtoGo - Queue" cmd /k "cd /d "%API_DIR%" && %CMD_QUEUE%"
start "OtoGo - Reverb" cmd /k "cd /d "%API_DIR%" && %CMD_REVERB%"
start "OtoGo - Log" powershell -NoProfile -NoExit -Command "Get-Content -LiteralPath '%LOG%' -Wait -Tail 30"
if "%ADA_MOBILE%"=="1" start "OtoGo - Mobile %WEB_PORT%" cmd /k "cd /d "%MOBILE_DIR%" && %CMD_MOBILE%"

:done
echo  Selesai. Jendela ini boleh ditutup.
timeout /t 5 >nul
exit /b 0
