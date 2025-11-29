@echo off
:: Bearsampp Cmder Launcher
:: This script launches Cmder with proper parameter handling

setlocal enabledelayedexpansion

:: Get Cmder root directory
set "CMDER_ROOT=%~dp0"
if "%CMDER_ROOT:~-1%"=="\" set "CMDER_ROOT=%CMDER_ROOT:~0,-1%"

:: Set Cmder executable
set "CMDER_EXE=%CMDER_ROOT%\Cmder.exe"

:: Build Cmder command line
set "CMDER_ARGS=/c "%CMDER_ROOT%""

:: Parse arguments passed to this script
:parse_args
if "%~1"=="" goto end_parse

:: Check for /Title parameter (convert to /x for ConEmu)
if /i "%~1"=="/Title" (
    set "TITLE_ARG=/x -Title "%~2""
    shift
    shift
    goto parse_args
)

:: Check for /Dir parameter (convert to /start for Cmder)
if /i "%~1"=="/Dir" (
    set "DIR_ARG=/start "%~2""
    shift
    shift
    goto parse_args
)

:: Check for /cmd parameter (convert to /x for ConEmu)
if /i "%~1"=="/cmd" (
    set "CMD_ARG=/x -cmd "%~2""
    shift
    shift
    goto parse_args
)

:: Unknown parameter, pass it through
set "CMDER_ARGS=!CMDER_ARGS! %~1"
shift
goto parse_args

:end_parse

:: Add parsed arguments
if defined DIR_ARG set "CMDER_ARGS=!CMDER_ARGS! !DIR_ARG!"
if defined TITLE_ARG set "CMDER_ARGS=!CMDER_ARGS! !TITLE_ARG!"
if defined CMD_ARG set "CMDER_ARGS=!CMDER_ARGS! !CMD_ARG!"

:: Launch Cmder
"%CMDER_EXE%" !CMDER_ARGS!

endlocal
