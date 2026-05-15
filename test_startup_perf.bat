@echo off
setlocal enabledelayedexpansion

echo Bearsampp Startup Performance Test
echo ==================================
echo.
echo Running 5 startup iterations to measure average time...
echo.

set TOTAL=0
set COUNT=5

for /L %%i in (1,1,%COUNT%) do (
    echo [%%i/%COUNT%] Starting bearsampp...

    for /F "tokens=*" %%A in ('powershell -Command "
        $sw = [System.Diagnostics.Stopwatch]::StartNew()
        & '.\bearsampp.exe' startup
        $sw.Stop()
        Write-Host $([Math]::Round($sw.ElapsedMilliseconds))
    "') do (
        set TIME=%%A
    )

    echo           Startup time: !TIME!ms
    set /A TOTAL=!TOTAL!+!TIME!
)

echo.
echo ==================================
set /A AVERAGE=!TOTAL!/%COUNT%
echo Average startup time: !AVERAGE!ms
echo.
echo Note: Run this before and after optimizations to compare.
echo Record these times in PERFORMANCE_ANALYSIS.md
