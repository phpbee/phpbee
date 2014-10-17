  <%--
	This script's using Apache Commons Fileupload library (which require Apache Commons IO)
	to parse request, they can be downloaded from:
	commons-io: http://commons.apache.org/downloads/download_io.cgi
	commons-fileupload: http://commons.apache.org/downloads/download_fileupload.cgi
	
	See documentation of your web server to get an information about where you should place this libraries. 
	Ex. TomCat:
	"For classes and resources specific to a particular web application, place unpacked classes and resources
	under /WEB-INF/classes of your web application archive, or place JAR files containing those classes and resources
	under /WEB-INF/lib of your web application archive."
	--%>

  <%--
	Include required libraries and java classes:
  --%>
  <%@ page import="org.apache.commons.fileupload.servlet.ServletFileUpload" %>
  <%@ page import="org.apache.commons.fileupload.FileItem" %>
  <%@ page import="org.apache.commons.fileupload.disk.DiskFileItemFactory" %>
  <%@ page import="java.util.List" %>
  <%@ page import="java.io.File" %>
  <%@ page import="org.bis.dbslite.backing.FileTransferList" %>
  <%@ page import="org.bis.dbslite.util.Utils" %>
  <%@ page import="java.io.InputStream" %>
  <%@ page import="javax.faces.context.FacesContext" %>
  <%@ page import="java.net.URLDecoder" %>
  <%@ page import="java.io.FileOutputStream" %>
  <%@ page import="java.io.FileInputStream" %>
  <%
	final String openTag = "<multipowupload>";
	final String closeTag = "</multipowupload>";
	
	Utils.log("D", "uploadchunkedfiles.jsp start");
  
  	String tmp = null;
  	tmp = request.getParameter("action");
	boolean querySize = tmp == null ? false : tmp.equals("check");
	boolean upload = tmp == null ? false : tmp.equals("upload");
	Utils.log("D", new StringBuilder("Action check = ").append(querySize).toString());
	Utils.log("D", new StringBuilder("Action upload = ").append(upload).toString());
	
	tmp = null;
	tmp = request.getParameter("isMultiPart");	
	boolean isMultiPart = tmp == null ? false : Boolean.parseBoolean(tmp);
	Utils.log("D", new StringBuilder("Multipart = ").append(isMultiPart).toString());
	
	tmp = null;
	tmp = request.getParameter("totalSize");
	Long fileSize = tmp == null ? 0L : Long.parseLong(tmp);
	Utils.log("D", new StringBuilder("File size = ").append(fileSize).toString());
	
	String uniqueID = request.getParameter("fid");
	Utils.log("D", new StringBuilder("Unique ID = ").append(uniqueID).toString());
	
	String comment = request.getParameter("Comment");
	if (comment != null)
		comment = URLDecoder.decode(comment,"8859_1");
	Utils.log("D", new StringBuilder("Comment = ").append(comment).toString());
	
	String tag = request.getParameter("Tag");
	if (tag != null)
		tag = URLDecoder.decode(tag,"8859_1");
	Utils.log("D", new StringBuilder("Tag = ").append(tag).toString());
	
	String userId = request.getParameter("uploadfrm:userid");
	if (userId != null)
		userId = URLDecoder.decode(userId,"8859_1");
	Utils.log("D", new StringBuilder("User ID = ").append(userId).toString());
	
	String fileName = request.getParameter("fileName");
	if (fileName == null || fileName.length() == 0)
		return;
	fileName = URLDecoder.decode(fileName,"8859_1");
	Utils.log("D", new StringBuilder("File name = ").append(fileName).toString());
	
	String tmpDir = System.getProperty("java.io.tmpdir");
	String tmpFile = tmpDir + "\\" + uniqueID + fileName;
	Utils.log("D", new StringBuilder("Temporary file = ").append(tmpFile).toString());
	File f = new File(tmpFile); 

  	out.println(openTag);
  	
	InputStream ips = null;
	
	if (querySize) {
		// assume we don't resume uploads, ie every upload loads a new file
		if (!f.exists())
			out.println("<ok size='0'/>");
		else
			out.println("<ok size='" + Long.toString(f.length()) + "'/>");
		
	} else if (upload) {
		if (isMultiPart)
			out.println("<error message=\"Multipart chunked upload is not supported at the moment\" />");
		else {
			
			// open the temp file for writing (appending if already exists)
			boolean append = f.exists();
			FileOutputStream fos = new FileOutputStream(f, append);

			ips = request.getInputStream();

			// write the content of the inputstream into the file
	        byte[] buffer = new byte[40960];
	        int bytesRead;
	        while ((bytesRead = ips.read(buffer)) != -1)
	        	fos.write(buffer, 0, bytesRead);
	        
	        fos.flush();
	        fos.close();
	        
	        f = new File(tmpFile);
			
	        // check if the file is already complete. If so, store it in DB and delete the temp file
	        if (f.exists() && f.length() >= fileSize) {
			
			FileInputStream streamIn = new FileInputStream(f);

			//write the file to SQL Server database
			String result = FileTransferList.writeFileToDB(userId, fileName, streamIn, fileSize);
			streamIn.close();
			
			if (result.indexOf("error") != -1) {
				out.println(new StringBuilder("<error message=\"Transfer error during transfer to back-end server: ")
					.append(result)
					.append("\"/>").toString());
			} else {
				// delete the tmpFile
				f.delete();		
				//out.println("<response>File " + fileName + " was successfully uploaded.</response>");
			}
		}
			out.println("<ok/>");
		}
	} else {
		out.println("<error message=\"Invalid request\"/>");
	}
  	out.println(closeTag);
  	out.flush();
	Utils.log("D", "uploadchunkedfiles.jsp end");
  	
  %>
