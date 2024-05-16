@echo off
setlocal

:: Set the root directory for the search
set "ROOT_DIR=%CD%"

:: Loop through each directory recursively from the root
for /R "%ROOT_DIR%" %%D in (.) do (
    :: Check if the directory name is 'current'
    if /I "%%~nxD"=="current" (
        echo Removing: "%%D"
        rd /S /Q "%%D"
    )
)

echo Done.
endlocal
