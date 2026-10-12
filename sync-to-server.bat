@echo off
echo ============================================
echo   ICT Asset Loan System - Sync Script
echo ============================================
echo.
echo Syncing project files to Apache htdocs...
echo.

robocopy "C:\Users\SYAHER IZHAM\asset-loan" "C:\xampp\htdocs\asset-loan" /MIR /XD ".git" "vendor" /NFL /NDL /NJH /NJS

echo.
echo [OK] Sync complete!
echo.
echo Server URL: http://10.142.10.237/asset-loan/
echo.
pause
