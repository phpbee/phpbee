<%@ Page language="VB"%>
<%						
	dim i as integer
	for i=0 to Request.Form.Count-1
		if Request.Form("MultiPowUploadFileName_" & i) <> nothing then
			Response.Write("File with name " & Request.Form("MultiPowUploadFileName_" & i) & " and size " & Request.Form("MultiPowUploadFileSize_" & i) & " uploaded successfully<br>")
		end if
		if Request.Form("MultiPowUploadUnuploadedFileName_" & i) <> nothing then
			Response.Write("File with name " & Request.Form("MultiPowUploadUnuploadedFileName_" & i) & " and size " & Request.Form("MultiPowUploadUnuploadedFileSize_" & i)  & " was not uploaded<br>")
		end if
	next		
%>