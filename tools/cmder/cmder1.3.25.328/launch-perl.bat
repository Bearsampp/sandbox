@echo off
:: Launch Cmder in Perl directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Perl directory
set "PERL_DIR=E:\Bearsampp-development\sandbox\tools\perl\perl5.38.2.2"

:: Launch Cmder with /start parameter and custom title
"%~dp0Cmder.exe" /start "%PERL_DIR%" /icon "Perl Console"
