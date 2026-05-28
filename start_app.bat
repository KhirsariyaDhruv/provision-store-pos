@echo off
title POS System - Local Server
cd /d "%~dp0"
echo Starting POS System...

:: Kill existing PHP to avoid conflict
taskkill /F /IM php.exe >nul 2>&1

:: Start PHP Server in background
echo Starting PHP Server...
:: Start MySQL Server if not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo MySQL is already running.
) else (
    echo Starting MySQL Database...
    start /B "" "C:\xampp\mysql_start.bat" >nul 2>&1
    timeout /t 5 >nul
)

:: Start PHP Server in background
echo Starting PHP Server...
start /B "" "C:\xampp\php\php.exe" -S localhost:8000 >nul 2>&1

echo Server Started! Opening Browser...
timeout /t 2 >nul
start http://localhost:8000/pos.php

echo.
echo ========================================
echo   POS SYSTEM IS RUNNING
echo   Do not close this window.
echo ========================================
echo.
pause
