<?php
error_reporting(E_ERROR);
$dirPath = dirname(__FILE__) . "/UploadedFiles";

$filename = isset($_GET["fileName"]) ? str_replace("../", "", $_GET["fileName"]) : "";
$querySize = isset($_GET["action"]) ? strtolower($_GET["action"]) == "check" ? true : false : false;
$upload = isset($_GET["action"]) ? strtolower($_GET["action"]) == "upload" ? true : false : false;
$fileSize = isset($_GET["totalSize"]) ? (int)$_GET["totalSize"] : 0;
$uniqueID = isset($_GET["fid"]) ? $_GET["fid"] : "";
$isMultiPart = isset($_GET["isMultiPart"]) ? $_GET["isMultiPart"] == "true" : false;
$openTag = "<multipowupload>";
$closeTag = "</multipowupload>";
$filePath = $dirPath . "/" . $uniqueID.$filename;

//Flash send file name in UTF-8 encoding. And in most cases you need not any conversion.
//But php for Windows have bug related to file name encoding in move_uploaded_file function.
// http://bugs.php.net/bug.php?id=47096
// If you use file names in national encodings, change the $filePath assignment consider
// encoding conversion by functions 'iconv()' or 'mb_convert_encoding()' as shown below:
// $target_encoding = "ISO-8859-1";
// $filePath = $dirPath . "/" . mb_convert_encoding($filename, $target_encoding , 'UTF-8');
// $filePath = $dirPath . "/" . iconv("utf-8", $target_encoding, $filename);
echo $openTag;

if ($querySize)
{
	if (file_exists($dirPath) && is_dir($dirPath))	
		if (file_exists($filePath))		
			echo "<ok size='".filesize($filePath)."'/>";
	else 
		echo "<ok size='0'/>";
}
else if($upload)
{	
	if (!is_writable($dirPath))	
		write_error("Error: cannot write to the specified directory.");
	else
	{
		//if mulltipart mode and there is no file form field in request , then write error
		if($isMultiPart && count($_FILES) <= 0)
			write_error("No chunk for save.");	
		//if can't open file for append , then write error
		if (!$file = fopen($filePath, "a")) 			
			write_error("Can't open file for write.");	
		
		//logic to read and save chunk posted with multipart
		if($isMultiPart)
		{
			$filearr = pos($_FILES);		
			if(!$input = file_get_contents($filearr['tmp_name']))
				write_error("Can't read from file.");
			else
				if(!fwrite($file, $input)) 
					write_error("Can't write to file.");			
		}
		//logic to read and save chunk posted as raw stream
		else
		{
			$input = file_get_contents("php://input");
			if(!fwrite($file, $input)) 
				write_error("Can't write to file.");			
		}
		fclose($file);
		
		//Upload complete if size of saved temp file >= size of source file.
		if(filesize($filePath) >= $fileSize)
		{	
			if(file_exists($dirPath."/" .$filename))
			{
				//delete file if exist				
				unlink($dirPath."/" .$filename);
				//or rename old or new file
			}
			//move file
			rename($filePath, $dirPath."/" .$filename);
			//here You can do some other actions when upload complete			
			echo "<response>File uploaded correctly</response>";
		}
		echo "<ok />";
	}
}

echo $closeTag;

function write_error($errstr)
{
	GLOBAL $closeTag;
    echo "<error message=\"".$errstr."\"/>";		
	echo $closeTag;
	exit;
}
?>
