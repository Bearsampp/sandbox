@echo off
:: Launch Cmder in Ruby directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Ruby directory
set "RUBY_DIR=E:\Bearsampp-development\sandbox\tools\ruby\ruby3.4.5"

:: Launch Cmder with /start parameter
"%~dp0Cmder.exe" /start "%RUBY_DIR%"
