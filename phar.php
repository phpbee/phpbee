<?php

$phar = new Phar('gs_libs.phar');
$phar->buildFromDirectory('libs');

if (Phar::canCompress(Phar::GZ))
{
	   if(file_exists('gs_libs.phar.gz')) unlink('gs_libs.phar.gz');
	   $phar->compress(Phar::GZ,'.phar.gz');
}

?>
