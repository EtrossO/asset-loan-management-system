@echo off
echo ============================================
echo   ICT Asset Loan System - Startup Script
echo ============================================
echo.

echo Starting MySQL...
net start MySQL80 2>nul
if %errorlevel%==0 (
    echo [OK] MySQL started
) else (
    echo [!!] MySQL may already be running
)

echo.
echo Starting Apache...
net start Apache2.4 2>nul
if %errorlevel%==0 (
    echo [OK] Apache started
) else (
    echo [!!] Apache may already be running
)

echo.
echo ============================================
echo   System IP: 10.142.11.77
echo   URL: http://10.142.11.77/asset-loan/
echo ============================================
echo.
pause
