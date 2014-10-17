<?php

for($i=0; $i<count($_POST); $i++)
{
	if(isset($_POST["MultiPowUploadFileName_". $i]))
	{
		echo "File with name " . $_POST["MultiPowUploadFileName_". $i]. " and size " . $_POST["MultiPowUploadFileSize_" . $i] . " uploaded successfully<br>";
	}
}


for($i=0; $i<count($_POST); $i++)
{
	if(isset($_POST["MultiPowUploadUnuploadedFileName_". $i]))
	{
		echo "File with name " . $_POST["MultiPowUploadUnuploadedFileName_". $i]." and size " . $_POST["MultiPowUploadUnuploadedFileSize_" . $i] ." was not uploaded<br>";
	}
}

?>