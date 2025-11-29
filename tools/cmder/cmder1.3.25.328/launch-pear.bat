@echo off
:: Launch Cmder in PEAR directory (PHP bin directory)
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set PEAR directory (PHP bin where PEAR is located)
set "PEAR_DIR=E:\Bearsampp-development\sandbox\bin\php\php8.4.2"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%PEAR_DIR%" /icon "PEAR Console"
