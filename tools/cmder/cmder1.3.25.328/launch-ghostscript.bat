@echo off
:: Launch Cmder in Ghostscript directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Ghostscript directory
set "GHOSTSCRIPT_DIR=E:\Bearsampp-development\sandbox\tools\ghostscript\ghostscript10.03.1"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%GHOSTSCRIPT_DIR%" /icon "Ghostscript Console"
