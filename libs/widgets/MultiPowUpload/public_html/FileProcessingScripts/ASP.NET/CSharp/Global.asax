<%@ Application Language="C#" %>
<script runat="server">

/* 

C# Global.asax file for ASP.NET sample. 
Workaround for Flash Player bug. Flash Player don't sends cookies in non-IE browsers.
MultiPowUpload has workaround-it sends Cookies at Post request by "MultiPowUpload_browserCookie" field.
This Global.asax parses this value and sets at Request.Cookies collection.
Also this script remove httpOnly attribut from all cookies to allow MultiPowUpload read them in browse using JavaScript.

NOTE: this issue occurs only when files uploaded using standard RFC compliant method.
	If you configure MultiPowUPload to use chunked upload mode, cookies correctly included into request headers in all browsers!
*/


	void Application_BeginRequest(object sender, EventArgs e)
	{
		/* Trying restore browser cookies because Flash Player do not send them in non-IE browsers*/

		try
		{
			string cookieParamName = "MultiPowUpload_browserCookie";			
			
			NameValueCollection browserCookie = new NameValueCollection();
			if(HttpContext.Current.Request.Form[cookieParamName] != null)
			{
				browserCookie = getCookieArray(HttpContext.Current.Request.Form[cookieParamName]);
				foreach (string s in browserCookie) 
					UpdateCookie(s, browserCookie[s]);	
			}
		}
		catch (Exception ex)
		{			

		}
	}
	void UpdateCookie(string cookieName, string cookieValue)
	{
		HttpCookie cookie = HttpContext.Current.Request.Cookies.Get(cookieName);
		if (cookie == null)
		{
			cookie = new HttpCookie(cookieName);
			HttpContext.Current.Request.Cookies.Add(cookie);
		}
		cookie.Value = cookieValue;
		HttpContext.Current.Request.Cookies.Set(cookie);
	}

	NameValueCollection getCookieArray(string cookie)
	{
		string[] split = cookie.Split(';');
		string[] splitParam = null;
		NameValueCollection  returnArr = new NameValueCollection();
        	foreach (string s in split) 
		{
			splitParam = s.Split('=');	
			if(splitParam.Length > 1)
				returnArr.Add(splitParam[0].Trim(), splitParam[1].Trim());			
			else
				returnArr.Add(splitParam[0].Trim(), "");			

		}
		return returnArr;

	}
	
	void Application_EndRequest(object sender, EventArgs e) 
	{
		//remove httpOnly attribute from all cookies to allow MultiPowUpload read them in browse using JavaScript.
		/*IF YOU HAVE EXCEPTION IN THIS FUNCTION:
		 Comment this code if your web application runs under ASP.NET version < 2.0
		 HttpOnly was introduced only in ASP.NEt 2.0, so there is no HttpOnly property in HttpCookie class under ASP.NET 1.0 or 1.1.
		*/
		if(Response.Cookies.Count > 0)
		   foreach(string s in Response.Cookies.AllKeys)        					
				   Response.Cookies[s].HttpOnly = false;
	}
	
</script>
