@echo off
echo ========================================
echo   Configuration Check - Symfony 6.4
echo ========================================
echo.

echo [1/5] Checking Symfony version...
php bin/console --version
echo.

echo [2/5] Checking configuration syntax...
php bin/console lint:yaml config/
echo.

echo [3/5] Checking Doctrine entities...
php bin/console doctrine:mapping:info
echo.

echo [4/5] Checking database connection...
php bin/console dbal:run-sql "SELECT VERSION()"
echo.

echo [5/5] Checking routes...
php bin/console debug:router | findstr /C:"app_login" /C:"app_admin"
echo.

echo ========================================
echo   All checks completed!
echo ========================================
pause
