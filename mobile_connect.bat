@echo off
:: Check for Admin Privileges
openfiles >nul 2>&1
if %errorlevel% NEQ 0 (
    echo Requesting Administrator privileges to open Firewall...
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

:: We are Admin now
cls
color 1f
echo ===================================================
echo       POS Mobile Access Launcher (ULTIMATE)
echo ===================================================
echo.

echo [STEP 1] RESETTING FIREWALL RULES...
netsh advfirewall firewall delete rule name="POS Server Port 8000" >nul 2>&1
netsh advfirewall firewall delete rule name="POS Server Port 8080" >nul 2>&1
netsh advfirewall firewall add rule name="POS Server Port 8080" dir=in action=allow protocol=TCP localport=8080 profile=any
echo Firewall rule for Port 8080 added.
echo.

echo [STEP 2] IDENTIFYING YOUR WI-FI IP...
echo.
ipconfig | findstr "IPv4"
echo.
echo (Use the one that looks like 192.168.x.x)
echo.

echo [STEP 3] CONNECT ON PHONE
echo 1. Connect Phone to SAME Wi-Fi.
echo 2. Open Browser.
echo 3. Type:  http://192.168.1.211:8080
echo    (Or replace 192.168.1.211 with your actual IP if different)
echo.

echo [STEP 4] SERVER STARTING ON PORT 8080...
echo.
cd /d "%~dp0"
php -S 0.0.0.0:8080
pause
