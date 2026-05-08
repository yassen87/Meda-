@echo off
echo Starting Meda E-commerce Website with XAMPP...
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp" (
    echo ERROR: XAMPP not found in C:\xampp\
    echo Please install XAMPP first from https://www.apachefriends.org/
    pause
    exit /b 1
)

REM Start Apache and MySQL services
echo Starting Apache and MySQL services...
cd /d "C:\xampp"
xampp_start.exe

REM Wait for services to start
timeout /t 5 /nobreak >nul

REM Open the website in browser
echo Opening website in browser...
start http://localhost/Meda/

echo.
echo Website should be running at: http://localhost/Meda/
echo Admin panel: http://localhost/Meda/admin/
echo.
echo Press any key to open XAMPP Control Panel...
pause >nul

REM Open XAMPP Control Panel
start xampp-control.exe

echo.
echo Setup complete! Your website is now running.
echo If you need help, check the setup_xampp.md file.
pause
