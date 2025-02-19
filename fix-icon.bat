@echo off
REM Batch file to download iconography, unzip it, and set the icon for bearsampp.exe using rcedit

REM Define the URL to download the iconography
SET ICON_URL=https://github.com/Bearsampp/sandbox/releases/download/iconography/Sandbox-iconography.zip

REM Define the path to the downloaded zip file
SET ZIP_PATH=Sandbox-iconography.zip

REM Download the iconography zip file
curl -L -o %ZIP_PATH% %ICON_URL%

REM Unzip the downloaded file into the root folder
tar -xf %ZIP_PATH%

REM Delete the zip file after extraction
del %ZIP_PATH%

REM Define the path to the icon
SET ICON_PATH=core\resources\homepage\img\icons\bearsampp.ico

REM Define the path to the executable
SET EXECUTABLE_PATH=bearsampp.exe

REM Check if rcedit-x64.exe is in the current directory or specify the full path
SET RESOURCE_HACKER_PATH=ResourceHacker.exe

REM Execute rcedit to set the icon
%RESOURCE_HACKER_PATH% -open "%EXECUTABLE_PATH%" -save "%EXECUTABLE_PATH%" -action modify -res "%ICON_PATH%", -mask ICONGROUP, MAINICON,

REM Check if the command was successful
IF %ERRORLEVEL% EQU 0 (
    echo Icon set successfully.
) ELSE (
    echo Failed to set icon. Please check the paths and try again.
)
