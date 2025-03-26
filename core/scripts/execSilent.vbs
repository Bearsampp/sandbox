Set objShell = WScript.CreateObject("WScript.Shell")
Set objFso = CreateObject("Scripting.FileSystemObject")
Set args = WScript.Arguments
num = args.Count
sargs = ""

If num = 0 Then
    WScript.Quit 1
End If

If num > 1 Then
    Dim argArray()
    ReDim argArray(num - 2)
    For k = 1 To num - 1
        argArray(k - 1) = args.Item(k)
    Next
    sargs = " " & Join(argArray, " ") & " "
End If

Return = objShell.Run("""" & args(0) & """" & sargs, 0, True)
