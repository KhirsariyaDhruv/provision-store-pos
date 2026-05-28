@echo off
title Provision Store POS Server
color 0A

echo ====================================================
echo      Starting Provision Store POS System
echo ====================================================
echo.
echo Attempting to start PHP Server on port 8000...
echo.

REM Try to check for PHP version
php -v >nul 2>&1
if %errorlevel% neq 0 (
    color 0C
    echo [ERROR] PHP is NOT recognized on this system.
    echo.
    echo Please make sure you have PHP installed.
    echo Recommended: Install XAMPP (https://www.apachefriends.org)
    echo.
    echo If XAMPP is already installed, try running this command from the XAMPP control panel shell,
    echo or add the PHP path (e.g., C:\xampp\php) to your System Environment Variables.
    echo.
    echo Press any key to exit...
    pause >nul
    exit
)

echo PHP found! Starting Server...
echo Access the app at: http://localhost:8000
echo.
echo Press Ctrl+C to stop the server.
echo.

:: Start MySQL Server if not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo MySQL is already running.
) else (
    echo Starting MySQL Database...
    start /B "" "C:\xampp\mysql_start.bat" >nul 2>&1
    timeout /t 5 >nul
)

php -S localhost:8000
pause
