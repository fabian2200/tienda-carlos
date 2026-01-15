@echo off
cd C:\sistema_ventas

REM Inicia el servidor Laravel
start "" php artisan serve --host 192.168.1.33 --port 8000

start "" "C:\peso_aplicacion.exe"