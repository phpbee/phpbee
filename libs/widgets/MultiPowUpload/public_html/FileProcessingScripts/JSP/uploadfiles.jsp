<%@ page import="org.apache.commons.fileupload.FileItem"  %>
<%@ page import="org.apache.commons.fileupload.servlet.ServletFileUpload"  %>
<%@ page import="org.apache.commons.fileupload.disk.DiskFileItemFactory"  %>
<%@ page import="java.util.List"  %>
<%@ page import="java.util.Iterator"  %>
<%@ page import="java.io.File"  %>


<%
/*
Additional components used in this example can be downloaded from here:

http://commons.apache.org/downloads/download_io.cgi
http://commons.apache.org/downloads/download_fileupload.cgi
*/

if (ServletFileUpload.isMultipartContent(request))
{
	ServletFileUpload servletFileUpload = new ServletFileUpload(new DiskFileItemFactory());
	String uploadDir = application.getRealPath(request.getServletPath());
	uploadDir = (new java.io.File(uploadDir)).getParent()+"\\UploadedFiles\\";
	
	try {
	  	List fileItemsList = servletFileUpload.parseRequest(request);
		Iterator it = fileItemsList.iterator();
		while (it.hasNext())
		{
			FileItem fileItem = (FileItem)it.next();
			if (fileItem.isFormField())
			{  
				out.println(fileItem.getFieldName()+" - "+fileItem.getString()+"<br>");
			}
			else
			{
				fileItem.write(new File( uploadDir+"\\"+(new File(fileItem.getName()).getName())));
				out.println("File "+fileItem.getName()+" was successfully uploaded.");
			}
		}
  	
	}
	catch (Exception ex) {
		out.println(ex.getMessage());
	}

}
out.println("<br>"); //At least one symbol should be sent to response!!!
%>