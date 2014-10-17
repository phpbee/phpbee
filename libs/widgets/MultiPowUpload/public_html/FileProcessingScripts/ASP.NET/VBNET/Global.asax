<%@ Application Language="VB" %>
<script runat="server">
' 
'Workaround for Flash Player bug. Flash Player don't sends cookies in non-IE browsers.
'MultiPowUpload has workaround-it sends Cookies at Post request by "MultiPowUpload_browserCookie" field.
'This Global.asax parses this value and sets at Request.Cookies collection.
'Also this script remove httpOnly attribut from all cookies to allow MultiPowUpload read them in browse using JavaScript.
'
'NOTE: this issue occurs only when files uploaded using standard RFC compliant method.
'	If you configure MultiPowUPload to use chunked upload mode, cookies correctly included into request headers in all browsers!


	Sub Application_BeginRequest(ByVal sender As Object, ByVal e As EventArgs)
		' Trying restore browser cookies because Flash Player do not send them in non-IE browsers

		Try			
			Dim cookieParamName As String = "MultiPowUpload_browserCookie"					

			Dim browserCookie as NameValueCollection = new NameValueCollection()
			if not HttpContext.Current.Request.Form(cookieParamName) Is Nothing Then			
				browserCookie = getCookieArray(HttpContext.Current.Request.Form(cookieParamName))
				
				if not browserCookie is Nothing then
					For Each s As String In browserCookie						
						UpdateCookie(s, browserCookie(s))
					Next s
				End If
			End If			
		Catch ex As Exception

		End Try
		
	End Sub
	
	Sub UpdateCookie(ByVal cookie_name As String, ByVal cookie_value As String)
		Dim cookie As System.Web.HttpCookie = HttpContext.Current.Request.Cookies.Get(cookie_name)
		If cookie Is Nothing Then
			cookie = New HttpCookie(cookie_name)
			HttpContext.Current.Request.Cookies.Add(cookie)
		End If
		cookie.Value = cookie_value
		HttpContext.Current.Request.Cookies.Set(cookie)
	End Sub

	Function getCookieArray(ByVal cookie as String) as NameValueCollection
		Dim splited as String() = cookie.Split(";")
		Dim splitParam as String() = Nothing
		Dim returnArr as NameValueCollection = new NameValueCollection()
		For Each s As String In splited
			splitParam = s.Split("=")
			if(splitParam.length > 1) Then
				returnArr.Add(splitParam(0).Trim(), splitParam(1).Trim())							
			else
				returnArr.Add(splitParam(0).Trim(), "")			
			End if

		Next s
		return returnArr

	End Function
	
	Sub Application_EndRequest(ByVal sender As Object, ByVal e As EventArgs)
		' remove httpOnly attribut from all cookies to allow MultiPowUpload read them in browse using JavaScript.
		'IF YOU HAVE EXCEPTION IN THIS FUNCTION:
		'Comment this code if your web application runs under ASP.NET version < 2.0
		'HttpOnly was introduced only in ASP.NEt 2.0, so there is no HttpOnly property in HttpCookie class under ASP.NET 1.0 or 1.1.
		if(Response.Cookies.Count > 0) then
		   For Each s As String In Response.Cookies.AllKeys
				   Response.Cookies(s).HttpOnly = false
			Next s
		End if
	End Sub

       
</script>