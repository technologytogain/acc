Set WinScriptHost = CreateObject("WScript.Shell")
WinScriptHost.Run Chr(34) & "D:\xampp\htdocs\AutoScriptRunner\cronjob.bat" & Chr(34), 0
Set WinScriptHost = Nothing