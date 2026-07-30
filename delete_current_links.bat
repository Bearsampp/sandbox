@echo off
setlocal enabledelayedexpansion

echo Checking for BEARSAMPP_PATH environment variable...
if not defined BEARSAMPP_PATH (
    echo Error: BEARSAMPP_PATH environment variable is not defined.
    echo Please make sure Bearsampp is properly installed and the variable is set.
    pause
    exit /b 1
)

echo Using Bearsampp path: %BEARSAMPP_PATH%
echo Searching recursively for "current" folder links in bin, apps, and tools...
echo.

set "DIRECTORIES=bin apps tools"
set "FOUND=0"

for %%d in (%DIRECTORIES%) do (
    set "FULL_PATH=%BEARSAMPP_PATH%\%%d"
    if exist "!FULL_PATH!" (
        echo Checking directory tree: !FULL_PATH!
        
        rem Check if "current" exists directly in the directory
        if exist "!FULL_PATH!\current\" (
            echo Found: !FULL_PATH!\current
            set /a FOUND+=1
            
            echo Deleting: !FULL_PATH!\current
            rmdir "!FULL_PATH!\current"
            if !errorlevel! neq 0 (
                echo Error: Could not delete !FULL_PATH!\current - it may not be a link or you may need admin rights.
            ) else (
                echo Successfully deleted !FULL_PATH!\current
            )
        )
        
        rem Recursively search for "current" in all subdirectories
        for /f "tokens=*" %%s in ('dir /b /s /ad "!FULL_PATH!"') do (
            if exist "%%s\current\" (
                echo Found: %%s\current
                set /a FOUND+=1
                
                echo Deleting: %%s\current
                rmdir "%%s\current"
                if !errorlevel! neq 0 (
                    echo Error: Could not delete %%s\current - it may not be a link or you may need admin rights.
                ) else (
                    echo Successfully deleted %%s\current
                )
            )
        )
    ) else (
        echo Warning: Directory !FULL_PATH! does not exist.
    )
    echo.
)

if %FOUND% equ 0 (
    echo No "current" folder links were found.
) else (
    echo Completed. Found and attempted to delete %FOUND% "current" folder links.
)

pause
