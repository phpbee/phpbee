<%@ Page language="c#"%>
<%						
	int i;
	for(i=0; i<Request.Form.Count; i++)
	{
		if(Request.Form["MultiPowUploadFileName_" + i]!=null)
		{
			Response.Write("File with name " + Request.Form["MultiPowUploadFileName_" + i] + " and size " + 			Request.Form["MultiPowUploadFileSize_" + i] + " uploaded successfully<br>");
		}
		if(Request.Form["MultiPowUploadUnuploadedFileName_" + i]!=null)
		{
			Response.Write("File with name " + Request.Form["MultiPowUploadUnuploadedFileName_" + i]+ " and size " + 			Request.Form["MultiPowUploadUnuploadedFileSize_" + i] + " was not uploaded<br>");
		}

	}
		
%>
