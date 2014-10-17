<cfloop from="0" to="#StructCount(Form)#" index="i">
        <cfif
StructKeyExists(Form,'MultiPowUploadFileName_#i#')>
                <cfoutput>File with name
#Evaluate("Form.MultiPowUploadFileName_" & i)# and
size #Evaluate("Form.MultiPowUploadFileSize_" & i)#
bytes upoaded successfully<br></cfoutput>
        </cfif>
	<cfif
StructKeyExists(Form,'MultiPowUploadUnuploadedFileName_#i#')>
                <cfoutput>File with name
#Evaluate("Form.MultiPowUploadUnuploadedFileName_" & i)# and
size #Evaluate("Form.MultiPowUploadUnuploadedFileSize_" & i)#
was not upoaded<br></cfoutput>
        </cfif>

</cfloop>