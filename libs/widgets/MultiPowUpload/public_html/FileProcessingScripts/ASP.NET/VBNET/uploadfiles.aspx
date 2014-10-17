<%@ Page Language="VB" AutoEventWireup="true" Debug="true" %>
<%@ Import Namespace="System.IO" %>
<script runat='server'>					
		Protected Sub Page_Load(ByVal sender As Object, ByVal e As EventArgs)
		
			dim FolderToSave as String = Server.MapPath("") & "\UploadedFiles\"
			If String.IsNullOrEmpty(Request.QueryString("chunkedUpload")) Then
				dim myFile as HttpPostedFile = Request.Files(0)
				if not myfile is nothing andalso myFile.FileName <>"" then
					myFile.SaveAs(FolderToSave & System.IO.Path.GetFileName(myFile.FileName))
					Response.Write("File " & myFile.FileName & " was successfully uploaded.")
            End If
            Response.Write("<br>") 'At least one symbol should be sent to response!!!		
			else
				Dim tempPath As String = FolderToSave
				Dim fileName As String = Request.QueryString("FileName")
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
				If String.IsNullOrEmpty(Request.QueryString("QuerySize")) Then
					querySize = False
				Else
					querySize =  Boolean.Parse(Request.QueryString("QuerySize"))
				End If
				
				Dim complete As Boolean
				If String.IsNullOrEmpty(Request.QueryString("Complete")) Then
					complete = False
				Else
					complete = Boolean.Parse(Request.QueryString("Complete"))
				End If
			   
				Dim fileSize As Long
				If String.IsNullOrEmpty(Request.QueryString("totalSize")) Then
					fileSize = 0
				Else
					fileSize = Long.Parse(Request.QueryString("totalSize"))
				End If
			  
				
				Dim uniqueID As String
				If String.IsNullOrEmpty(Request.QueryString("FileId")) Then
					uniqueID = String.Empty
				Else
					uniqueID = HttpUtility.UrlDecode(Request.QueryString("FileId"))
				End If
			   
				If (String.IsNullOrEmpty(fileName) OrElse String.IsNullOrEmpty(tempPath)) Then
					Return
				End If
				Dim dirPath As String = GetPath(tempPath)
				Dim filePath As String = FolderToSave
				FolderToSave = GetPath(FolderToSave)
                Response.Clear()
				filePath = Path.Combine(dirPath, (uniqueID + fileName))
				Dim fi As FileInfo = New FileInfo(filePath)
				If querySize Then
					If Not Directory.Exists(dirPath) Then
						WriteError("The path for file storage not found on the server."&dirPath)
					ElseIf Not fi.Exists Then
						Response.Write("0")
					Else
						Response.Write(fi.Length.ToString)
                End If
                return
				Else
					Dim fs As FileStream = Nothing
					Try                
                    If (isMultiPart AndAlso (Request.Files.Count < 1)) Then
                        WriteError("No chunk for save!")
                        Return
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
								File.Move(filePath, Path.Combine(FolderToSave, fileName))
							End If
							' Place here the code making postprocessing of the uploaded file (moving to other location, database, etc). 
							Response.Write("File " & fileName & " was successfully uploaded.<br/>")
						End If
						Response.Flush()
					Catch ex As Exception				
						If (Not (fs) Is Nothing) Then
							 fs.Close()
						End If
						WriteError("Error: " & ex.Message)
					End Try
            End If
			end if
        
        Response.Flush()
		End Sub
		
		Private Sub WriteError(ByVal err As String)
			Response.Write("Error: "&err)
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
