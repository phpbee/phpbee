<cfif isDefined('Form.Filedata')>
        <cfif Form.Filedata is not ''>
                <cfset dest =
GetDirectoryFromPath(ExpandPath("UploadedFiles/"))>
                <cffile action="upload" filefield="Filedata"
destination="#dest#" nameconflict="overwrite">
                <cfif cffile.fileWasSaved>
                        <cfoutput>File #cffile.clientFile# was successfully uploaded.</cfoutput>
                <cfelse>
                        <cfoutput>An error occurred uploading
#cffile.clientFile#.<br></cfoutput>
                </cfif>
        </cfif>
</cfif>
<cfoutput><br></cfoutput>