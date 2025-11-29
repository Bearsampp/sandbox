@echo off
:: Launch Cmder in Composer directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Composer directory
set "COMPOSER_DIR=E:\Bearsampp-development\sandbox\tools\composer\composer2.8.9"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%COMPOSER_DIR%" /icon "Composer Console"
