Option Explicit

' Constants
Const ForAppending = 8
Const MAX_LOG_SIZE = 1048576 ' 1MB

' Objects
Dim randomShell, randomFso, randomArgs
Dim scriptPath, logPath, logFile

' Initialize core objects
Set randomShell = CreateObject("WScript.Shell")
Set randomFso = CreateObject("Scripting.FileSystemObject")
Set randomArgs = WScript.Arguments

' Validate argument count
If randomArgs.Count < 1 Then
    WScript.Echo "Error: Missing executable path argument"
    WScript.Quit 1
End If

' Configure logging
InitializeLogging

On Error Resume Next

Dim command, arguments, exitCode
command = randomArgs(0)
arguments = BuildArguments

LogInfo "Attempting to execute: " & command & " " & arguments
LogInfo "Working directory: " & randomShell.CurrentDirectory

' Execute command
exitCode = randomShell.Run(Chr(34) & command & Chr(34) & " " & arguments, 0, True)

If Err.Number <> 0 Then
    LogError "Execution failed: " & Err.Description & " (0x" & Hex(Err.Number) & ")"
    WScript.Quit 1
End If

LogInfo "Execution completed with exit code: " & exitCode
WScript.Quit exitCode

' ---------- Support Functions ----------
Sub InitializeLogging
    scriptPath = randomFso.GetParentFolderName(WScript.ScriptFullName)
    logPath = randomFso.BuildPath(scriptPath, "..\logs\")
    
    If Not randomFso.FolderExists(logPath) Then
        randomFso.CreateFolder(logPath)
        If Err.Number <> 0 Then
            WScript.Echo "Log path creation failed: " & Err.Description
            WScript.Quit 1
        End If
    End If
    
    logFile = randomFso.BuildPath(logPath, "bearsampp-vbs.log")
    CheckLogSize
End Sub

Function BuildArguments
    Dim argArray, i
    If randomArgs.Count > 1 Then
        ReDim argArray(randomArgs.Count - 2)
        For i = 1 To randomArgs.Count - 1
            argArray(i - 1) = randomArgs(i)
        Next
        BuildArguments = Join(argArray, " ")
    Else
        BuildArguments = ""
    End If
End Function

Sub CheckLogSize
    If randomFso.FileExists(logFile) Then
        If randomFso.GetFile(logFile).Size > MAX_LOG_SIZE Then
            randomFso.MoveFile logFile, logFile & "." & FormatDateTime(Now, 2) & ".bak"
        End If
    End If
End Sub

Sub LogError(message)
    WriteToLog "ERROR", message
End Sub

Sub LogInfo(message)
    WriteToLog "INFO", message
End Sub

Sub WriteToLog(level, message)
    On Error Resume Next
    Dim logStream
    Set logStream = randomFso.OpenTextFile(logFile, ForAppending, True)
    If Err.Number = 0 Then
        logStream.WriteLine Now & " [" & level & "] " & message
        logStream.Close
    Else
        WScript.Echo "Log write failed: " & Err.Description
        Err.Clear
    End If
End Sub
