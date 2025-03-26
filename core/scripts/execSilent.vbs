On Error Resume Next
Err.Clear

Dim randomShell, randomFso, randomArgs
randomShell = Util.random(15, false)
randomFso = Util.random(15, false)
randomArgs = Util.random(15, false)

Set randomShell = WScript.CreateObject("WScript.Shell")
Set randomFso = CreateObject("Scripting.FileSystemObject")
Set randomArgs = WScript.Arguments

If Err.Number <> 0 Then
    Util.logError("VBS error: " & Err.Description)
End If

num = randomArgs.Count
sargs = ""

If num = 0 Then
    WScript.Quit 1
End If

If num > 1 Then
    Dim argArray()
    ReDim argArray(num - 2)
    For k = 1 To num - 1
        argArray(k - 1) = randomArgs.Item(k)
    Next
    sargs = " " & Join(argArray, " ") & " "
End If

Return = randomShell.Run("""" & randomArgs(0) & """" & sargs, 0, True)
