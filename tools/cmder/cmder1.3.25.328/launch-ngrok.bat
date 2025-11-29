@echo off
:: Launch Cmder in Ngrok directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Ngrok directory
set "NGROK_DIR=E:\Bearsampp-development\sandbox\tools\ngrok\ngrok3"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%NGROK_DIR%" /icon "Ngrok Console"
