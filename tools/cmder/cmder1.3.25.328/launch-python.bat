@echo off
:: Launch Cmder in Python directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Python directory
set "PYTHON_DIR=E:\Bearsampp-development\sandbox\tools\python\python3.13.2"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%PYTHON_DIR%" /icon "Python Console"
