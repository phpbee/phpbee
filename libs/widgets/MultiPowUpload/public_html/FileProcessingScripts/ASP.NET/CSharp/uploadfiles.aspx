<%@ Page Language="C#" AutoEventWireup="true" %>
<%@ Import Namespace="System.IO" %>
<script runat="server">

    protected void Page_Load(object sender, EventArgs e)
    {	
		string FolderToSave = Server.MapPath("") + "\\UploadedFiles\\";
		/*-------------------------------------------------------------------------
		* First part of this script is for regular upload method (RFC based) 
		*/
		if(string.IsNullOrEmpty(Request.QueryString["chunkedUpload"]))
		{		
			if(Request.Files != null && Request.Files.Count > 0)	
			{
				HttpPostedFile myFile = Request.Files[0];
				if(myFile != null && myFile.FileName !="")
				{
					myFile.SaveAs(FolderToSave + System.IO.Path.GetFileName(myFile.FileName));
					Response.Write("File " + myFile.FileName + " was successfully uploaded.");
				}
			}
			Response.Write("<br>"); //At least one symbol should be sent to response!!!			
		}
		/*-------------------------------------------------------------------------
		* The second part is for chunked upload method used by silverlight uploader
		*/
		else
		{
			bool isMultiPart = string.IsNullOrEmpty(Request.QueryString["isMultiPart"]) ? false : bool.Parse(Request.QueryString["isMultiPart"]);
			string fileName = Request.QueryString["FileName"];
			string fileComment = Request.QueryString["Comment"];
			string tag = Request.QueryString["Tag"];
			fileName = HttpUtility.UrlDecode(fileName).Replace("..\\", "");
			bool complete = string.IsNullOrEmpty(Request.QueryString["Complete"]) ? true : bool.Parse(Request.QueryString["Complete"]);
			bool querySize = string.IsNullOrEmpty(Request.QueryString["QuerySize"]) ? false : bool.Parse(Request.QueryString["QuerySize"]);
			long startByte = string.IsNullOrEmpty(Request.QueryString["StartByte"]) ? 0 : long.Parse(Request.QueryString["StartByte"]); ;

			if (string.IsNullOrEmpty(fileName) || string.IsNullOrEmpty(FolderToSave)) return;

			string dirPath = FolderToSave;
			string filePath;

			if (FolderToSave.StartsWith("/") || FolderToSave.StartsWith("~"))
			{
				try
				{
					dirPath = Server.MapPath(FolderToSave);
				}
				catch { }
			}

			filePath = System.IO.Path.Combine(dirPath, fileName);

			if (querySize)
			{
				if (!System.IO.Directory.Exists(dirPath))
				{
					Response.Write("The path for file storage not found on the server.");
					Response.Flush();
					return;
				}

				System.IO.FileInfo fi = new System.IO.FileInfo(filePath);
				if (!fi.Exists) Response.Write("0");
				else Response.Write(fi.Length.ToString());
				Response.Flush();
			}
			else
			{
				FileStream fs = null;
				try
				{	
					if(isMultiPart && Request.Files.Count < 1 )
                    {
						Response.Write("Error: No chunk for save!");
						Response.Flush();
						return;						
					}
                    if (startByte > 0 && File.Exists(filePath))  
					{
                        fs = File.Open(filePath, FileMode.Append);                        
						Response.Write(string.Format("Write chunk since byte {0}", startByte));
					}
                    else   
					{
                        fs = File.Create(filePath);
						Response.Write("Creating file...");
					}

                    if (fs != null)
                    {
                        if (!isMultiPart)
                            SaveFile(Request.InputStream, fs);
                        else
                            SaveFile(Request.Files[0].InputStream, fs);
                        fs.Close();
                    }
					
					if (complete)
					{
						Response.Clear();					
						Response.Write("File " + fileName + " was successfully uploaded.");
						// Place here the code making postprocessing of the uploaded file (moving to other location, database, etc). 
					}

					Response.Flush();
				}
				catch (Exception)
				{
					if (fs != null)
						fs.Close();
					Response.Clear();
					Response.Write("Write error.");
					Response.Flush();
				}
			}
		}
    }

    private void SaveFile(System.IO.Stream stream, System.IO.FileStream fs)
    {
        byte[] buffer = new byte[4096];
        int bytesRead;
        while ((bytesRead = stream.Read(buffer, 0, buffer.Length)) != 0)
        {
            fs.Write(buffer, 0, bytesRead);
        }
    }
</script>


