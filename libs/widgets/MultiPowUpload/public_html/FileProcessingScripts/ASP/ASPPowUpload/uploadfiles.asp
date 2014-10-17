<% @ Language="VBScript" CodePage = 65001 %>
	<%	
		dim theForm, myFile, FolderToSave, i
		
		Response.Write("Upload result:<br>") 'At least one symbol should be sent to response!!!
		
		Set theForm = Server.CreateObject("ASPPowUpload.Upload") 
		Response.Write("after form :<br>") 'At least one symbol should be sent to response!!!
		theForm.Save("C:\Temp\")
		
		FolderToSave = Server.MapPath(".") & "\UploadedFiles\"
		if not theForm.Files is Nothing then
			if theForm.Files.Count > 0 then
				Set myFile = theForm.Files(1)
				if myFile.SafeFileName <> "" then
					myFile.SaveAs FolderToSave & myFile.SafeFileName, true
					Response.Write("File was saved to folder <b>" & FolderToSave & "</b><br><hr>")
				end if
			end if
		end if
	%>