@echo off
rem ============================================================
rem  Voxnup Not Laravel (VNL) — Windows konsol kisayolu
rem  "vnl migrate --run" yazabilmek icin. cmd.exe uzantiyi
rem  PATHEXT uzerinden kendisi bulur; PowerShell'de ".\vnl".
rem ============================================================
setlocal

where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo   PHP komut satirinda bulunamadi.
    echo   PATH'e ekleyin ya da Docker ile calistirin:
    echo.
    echo     docker run --rm -v "%cd%":/app -w /app php:8.2-cli php vnl.php %*
    echo.
    exit /b 1
)

php "%~dp0vnl.php" %*
