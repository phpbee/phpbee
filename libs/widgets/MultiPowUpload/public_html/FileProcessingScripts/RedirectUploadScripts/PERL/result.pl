#!/usr/bin/perl -w 

use CGI; 

$query = new CGI; 

print $query->header ( ); 
print $query->start_html();

@names = $query->param;
%hnames = ();
foreach $item (@names) { $hnames{$item} = $query->param($item) }
for($i=0; $i<@names; $i++)
{
	if(defined($hnames{"MultiPowUploadFileName_$i"}) and defined($hnames{"MultiPowUploadFileSize_$i"})) 
	{
		print "File with name ", $hnames{"MultiPowUploadFileName_$i"},
			" and size ", $hnames{"MultiPowUploadFileSize_$i"}, " uploaded successfully",
			$query->br();
	}

	if(defined($hnames{"MultiPowUploadUnuploadedFileName_$i"})) 
	{
		print "File with name ", $hnames{"MultiPowUploadUnuploadedFileName_$i"},
			" and size ", $hnames{"MultiPowUploadUnuploadedFileSize_$i"}, " was not uploaded.",
			$query->br();
	}

}
print $query->end_html();
