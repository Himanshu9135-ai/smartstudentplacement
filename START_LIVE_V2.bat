@echo off
title Smart Placement Portal V2 - Live Starter
color 0b
cls

echo ========================================================
echo     SMART STUDENT PLACEMENT PORTAL (V2) - LIVE STARTER
echo ========================================================
echo.
echo [1/3] Checking XAMPP Apache and MySQL services...
echo.

:: Check port 80 & 3306
echo [2/3] Local Access URLs:
echo       - Laptop Browser : http://localhost/SmartStudentPlacement_V2/
echo       - Same Wi-Fi     : http://172.20.7.211/SmartStudentPlacement_V2/
echo.
echo [3/3] Starting Secure Ngrok Live Tunnel...
echo.
echo ========================================================
echo   PUBLIC LINK:
echo   https://sizing-substance-june.ngrok-free.dev/SmartStudentPlacement_V2/
echo ========================================================
echo.
echo NOTE: Mobile par pehli baar kholne par 'Visit Site' button tap karein.
echo Is window ko khula rehne dein (Minimize kar sakte hain).
echo.

if exist "C:\xampp\ngrok.exe" (
    C:\xampp\ngrok.exe http 80 --url=sizing-substance-june.ngrok-free.dev
) else (
    echo Error: C:\xampp\ngrok.exe not found!
    pause
)
