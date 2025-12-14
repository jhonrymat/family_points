@echo off
echo ==========================================
echo    Family Points - Servidor Local
echo ==========================================
echo.
echo 1. Si es la primera vez, abre http://localhost:8000/setup_wizard.php para configurar la base de datos.
echo 2. Si ya configuraste, abre http://localhost:8000/
echo.
echo Presiona Ctrl+C para detener el servidor.
echo.
php -S localhost:8000
