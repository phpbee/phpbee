<%@ Page Language="VB" AutoEventWireup="true" %>
<%@ Import Namespace="System.IO" %>
<script runat='server'>
    
    Private savePath As String = Server.MapPath("")+"/UploadedFiles"
    
    Private tempPath As String = Server.MapPath("")+ "/UploadedFiles"
    
    Private openTag As String = "<multipowupload>"
    
    Private closeTag As String = "</multipowupload>"
    
    Protected Sub Page_Load(ByVal sender As Object, ByVal e As EventArgs)
        Dim fileName As String = Request.QueryString("fileName")
        If Not String.IsNullOrEmpty(fileName) Then
            fileName = HttpUtility.UrlDecode(fileName).Replace("..\\", "")
        End If
        Dim isMultiPart As Boolean 
        If String.IsNullOrEmpty(Request.QueryString("isMultiPart")) Then
            isMultiPart = False
        Else
            isMultiPart = Boolean.Parse(Request.QueryString("isMultiPart"))
        End If
        
        Dim querySize As Boolean
        If String.IsNullOrEmpty(Request.QueryString("action")) Then
            querySize = False
        Else
            querySize = Request.QueryString("action").ToLower.Equals("check")
        End If
        
        Dim upload As Boolean
        If String.IsNullOrEmpty(Request.QueryString("action")) Then
            upload = False
        Else
            upload = Request.QueryString("action").ToLower.Equals("upload")
        End If
       
        Dim fileSize As Long
        If String.IsNullOrEmpty(Request.QueryString("totalSize")) Then
            fileSize = 0
        Else
            fileSize = Long.Parse(Request.QueryString("totalSize"))
        End If
      
        
        Dim uniqueID As String
        If String.IsNullOrEmpty(Request.QueryString("fid")) Then
            uniqueID = String.Empty
        Else
            uniqueID = HttpUtility.UrlDecode(Request.QueryString("fid"))
        End If
       
        If (String.IsNullOrEmpty(fileName) OrElse String.IsNullOrEmpty(tempPath)) Then
            Return
        End If
        Dim dirPath As String = GetPath(tempPath)
        Dim filePath As String
        savePath = GetPath(savePath)
        Response.Write(openTag)
        filePath = Path.Combine(dirPath, (uniqueID + fileName))
        Dim fi As FileInfo = New FileInfo(filePath)
        If querySize Then
            If Not Directory.Exists(dirPath) Then
                WriteError("The path for file storage not found on the server."+dirPath)
            ElseIf Not fi.Exists Then
                Response.Write("<ok size='0'/>")
            Else
                Response.Write(("<ok size='" _
                                + (fi.Length.ToString + "'/>")))
            End If
        Else
			Dim fs As FileStream = Nothing
            Try                
                If (isMultiPart _
                            AndAlso (Request.Files.Count < 1)) Then
                    WriteError("No chunk for save!")
                End If
                If File.Exists(filePath) Then
                    fs = File.Open(filePath, FileMode.Append)
                Else
                    fs = File.Create(filePath)
                End If
                If (Not (fs) Is Nothing) Then
                    If Not isMultiPart Then
                        SaveFile(Request.InputStream, fs)
                    Else
                        SaveFile(Request.Files(0).InputStream, fs)
                    End If
                    fs.Close()
                End If
                If (New FileInfo(filePath).Length >= fileSize) Then
                    'rename or move temp file                        
                    If File.Exists(Path.Combine(dirPath, fileName)) Then
                        File.Delete(filePath)
                    Else
                        File.Move(filePath, Path.Combine(savePath, fileName))
                    End If
                    ' Place here the code making postprocessing of the uploaded file (moving to other location, database, etc). 
					Response.Write("<response>File "&fileName&" was successfully uploaded.</response>");
                End If
                Response.Write("<ok />")
            Catch ex As Exception				
				If (Not (fs) Is Nothing) Then
					 fs.Close()
				End If
                WriteError("Error: " & ex.Message)
            End Try
        End If
        Response.Write(closeTag)
        Response.Flush()
    End Sub
    
    Private Sub WriteError(ByVal err As String)
        Response.Write(("<error message=""" _
                        + (err + """/>")))
        Response.Flush()
    End Sub
    
    Private Function GetPath(ByVal path As String) As String
        If (path.StartsWith("/") OrElse path.StartsWith("~")) Then
            Try 
                path = Server.MapPath(path)
            Catch ex As System.Exception
                
            End Try
        End If
        Return path
    End Function
    
    Private Sub SaveFile(ByVal stream As System.IO.Stream, ByVal fs As System.IO.FileStream)
        Dim buffer() As Byte = New Byte((40960) - 1) {}
        Dim bytesRead As Integer
        bytesRead = stream.Read(buffer, 0, buffer.Length)
        While (bytesRead <> 0)
            fs.Write(buffer, 0, bytesRead)
            bytesRead = stream.Read(buffer, 0, buffer.Length)
        End While
    End Sub
</script>
