<%@ Page Language="C#" AutoEventWireup="true" %>
<%@ Import Namespace="System.IO" %>
<script runat="server">

    private string savePath = "~/UploadedFiles";
	private string tempPath = "~/UploadedFiles";
	private string openTag = "<multipowupload>";
    private string closeTag = "</multipowupload>";

    protected void Page_Load(object sender, EventArgs e)
    {
       string fileName = Request.QueryString["fileName"];
            if (!string.IsNullOrEmpty(fileName))
                fileName = HttpUtility.UrlDecode(fileName).Replace("..\\", "");
           
            bool isMultiPart = string.IsNullOrEmpty(Request.QueryString["isMultiPart"]) ? false : bool.Parse(Request.QueryString["isMultiPart"]);
            bool querySize = string.IsNullOrEmpty(Request.QueryString["action"]) ? false : Request.QueryString["action"].ToLower().Equals("check")? true: false;
            bool upload = string.IsNullOrEmpty(Request.QueryString["action"]) ? false : Request.QueryString["action"].ToLower().Equals("upload")? true: false;
            long fileSize = string.IsNullOrEmpty(Request.QueryString["totalSize"]) ? 0 : long.Parse(Request.QueryString["totalSize"]); ;
          
            string uniqueID = Request.QueryString["fid"] != null ?
                HttpUtility.UrlDecode(Request.QueryString["fid"].ToString()) : string.Empty;
            string comment = Request.QueryString["Comment"] != null ?
                HttpUtility.UrlDecode(Request.QueryString["Comment"].ToString()) : string.Empty;
            string tag = Request.QueryString["Tag"] != null ?
                HttpUtility.UrlDecode(Request.QueryString["Tag"].ToString()) : string.Empty;

            

            if (string.IsNullOrEmpty(fileName) || string.IsNullOrEmpty(tempPath)) return;

            string dirPath = GetPath(tempPath);
            string filePath;
			savePath = GetPath(savePath);
           

           Response.Write(openTag);

            filePath = Path.Combine(dirPath, uniqueID+fileName);
			
            FileInfo fi = new FileInfo(filePath);
            if (querySize)
            {
                if (!Directory.Exists(dirPath))                
                    WriteError( "The path for file storage not found on the server.");                    
                else                
                    if (!fi.Exists)Response.Write("<ok size='0'/>");
                    else Response.Write("<ok size='" + fi.Length.ToString() + "'/>");               
            }
            else
            {
				FileStream fs = null;
                try
                {
                    
                    if(isMultiPart && Request.Files.Count < 1 )
                        WriteError( "No chunk for save!");    
                    if (File.Exists(filePath))                    
                        fs = File.Open(filePath, FileMode.Append);                        
                    else                    
                        fs = File.Create(filePath);

                    if (fs != null)
                    {
                        if (!isMultiPart)
                            SaveFile(Request.InputStream, fs);
                        else
                            SaveFile(Request.Files[0].InputStream, fs);
                        fs.Close();
                    }

                    if ((new FileInfo(filePath)).Length >= fileSize)
                    {    
                        //rename or move temp file						
						if(File.Exists(Path.Combine(dirPath, fileName)))							
							//File.Delete(Path.Combine(savePath, fileName)); and then move temp file to destination folder
							//File.Move(filePath , Path.Combine(savePath, fileName));
							//or remove  temp file and keep first copy of file
							File.Delete(filePath);
						else
							File.Move(filePath , Path.Combine(savePath, fileName));
                        
                         // Place here the code making postprocessing of the uploaded file (moving to other location, database, etc). 
						Response.Write("<response>File "+fileName+" was successfully uploaded.</response>");
                    }
                    
                        
                   Response.Write("<ok />");
                    
                   

                }
                catch (Exception ex)
                {     
					if (fs != null)
						fs.Close();
                    WriteError( "Error: " + ex.Message);                  
                }
            }
           Response.Write(closeTag);
           Response.Flush();
    }

    private void WriteError(String error)
	{	        
		Response.Write("<error message=\""+error+"\"/>");			      
		Response.Flush();
	}
	
	private String GetPath(String path)
	{
			if (path.StartsWith("/") || path.StartsWith("~"))
            {
                try
                {
                    path = Server.MapPath(path);
                }
                catch { }
            }
			return path;
	}
	
    private void SaveFile(System.IO.Stream stream, System.IO.FileStream fs)
    {
        byte[] buffer = new byte[40960];
        int bytesRead;
        while ((bytesRead = stream.Read(buffer, 0, buffer.Length)) != 0)
        {
            fs.Write(buffer, 0, bytesRead);
        }
    }
</script>

