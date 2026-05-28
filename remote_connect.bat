@echo off
cls
color 0a
echo ===================================================
echo       POS CLOUD CONNECT (Ngrok Alternative)
echo ===================================================
echo.

:: ENSURE CORRECT DIRECTORY
cd /d "%~dp0"

echo [STEP 1] STARTING LOCAL SERVER...
echo Stopping old servers...
taskkill /F /IM php.exe >nul 2>&1
echo Starting new server...
start /B C:\xampp\php\php.exe -S 0.0.0.0:8000 >nul 2>&1
echo Server running on Port 8000.
echo.

echo [STEP 2] GENERATING PUBLIC URL...
echo.
echo ---------------------------------------------------
echo WAITING FOR URL... (Look for Green Text below)
echo If asked "Are you sure you want to continue connecting", type: yes
echo ---------------------------------------------------
echo.

:: Using Serveo
echo Attempting to connect to Serveo...
echo.
echo ON YOUR PHONE, TYPE THE URL shown below (Green text):
echo.
ssh -R 80:localhost:8000 serveo.net
pause
