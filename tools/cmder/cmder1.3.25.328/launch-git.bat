yes@echo off
:: Launch Cmder in Git directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Git directory
set "GIT_DIR=E:\Bearsampp-development\sandbox\tools\git\git2.50.1"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%GIT_DIR%" /icon "Git Console"
