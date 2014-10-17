<%
int i = 0;
while(true)
{
	if(request.getParameter("MultiPowUploadFileName_"+i) != null)		
		out.println("File with name "+request.getParameter("MultiPowUploadFileName_"+i)+" and size "+request.getParameter("MultiPowUploadFileSize_"+i)+" uploaded successfully.<br>");	
	
	if(request.getParameter("MultiPowUploadUnuploadedFileName_"+i) != null)	
		out.println("File with name "+request.getParameter("MultiPowUploadUnuploadedFileName_"+i)+" and size "+request.getParameter("MultiPowUploadUnuploadedFileSize_"+i)+" was not uploaded.<br>");	
	
	if(request.getParameter("MultiPowUploadUnuploadedFileName_"+i) == null &&
		request.getParameter("MultiPowUploadFileName_"+i) == null)
			break;
	
	i++;
}
%>