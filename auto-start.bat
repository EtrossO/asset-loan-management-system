@echo off
:: Auto-start script for ICT Asset Loan System
:: Place this in Windows Startup folder for automatic startup

echo Starting ICT Asset Loan System...

:: Start MySQL
net start MySQL80 2>nul
if %errorlevel%==0 (
    echo [OK] MySQL started
) else (
    echo [OK] MySQL already running
)

:: Start Apache
net start Apache2.4 2>nul
if %errorlevel%==0 (
    echo [OK] Apache started
) else (
    echo [OK] Apache already running
)

:: Wait a moment then verify
timeout /t 2 /nobreak >nul
netstat -ano | findstr ":80 :3306" | findstr "LISTENING" >nul
if %errorlevel%==0 (
    echo [OK] System is ready at http://10.142.10.237/asset-loan/
) else (
    echo [!!] System may not be ready. Check services.
)

exit
