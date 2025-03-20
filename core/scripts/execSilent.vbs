' Simple VBScript to execute a command silently
Set objShell = WScript.CreateObject("WScript.Shell")
Set args = WScript.Arguments
num = args.Count
sargs = ""

If num = 0 Then
    WScript.Quit 1
End If

If num > 1 Then
    sargs = " "
    For k = 1 To num - 1
        anArg = args.Item(k)
        sargs = sargs & anArg & " "
    Next
End If

' Execute the command with window style 0 (hidden) and wait for completion
objShell.Run """" & args(0) & """" & sargs, 0, True

' No cleanup logic - this prevents potential runaway processes
