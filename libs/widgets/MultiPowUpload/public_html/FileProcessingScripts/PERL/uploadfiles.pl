#!/usr/bin/perl -w 

	use CGI; 
	use File::Basename;
	use Encode;

	$query = new CGI; 
	my $target_encoding = "ISO-8859-1";

	print $query->header ( ); 
	print $query->start_html();

	print "Upload result:<br>"; # At least one symbol should be sent to response!!!
		
	$upload_dir = dirname($ENV{'PATH_TRANSLATED'})."/UploadedFiles/";
	@names = $query->param;
	foreach $param (@names) 
	{
		my $filename = $query->param($param);
		$filename =~ s/.*[\/\\](.*)/$1/;
		$filename = encode($target_encoding, decode_utf8($filename));
		my $upload_filehandle = $query->upload($param);
		
		if(defined($upload_filehandle))	
		{
			open UPLOADFILE, ">$upload_dir\\$filename"; 
			binmode UPLOADFILE; 
			while ( <$upload_filehandle> ) 
			{ 
			 print UPLOADFILE; 		 
			} 		
			close UPLOADFILE;
			print "File $filename was successfully uploaded.";		
		}
	}
	print $query->end_html();

	
