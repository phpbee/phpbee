#!/bin/sh

PHPUNIT=`phpunit run.php`
if [ "$?" -ne "0" ]
then	
	echo $PHPUNIT | mail -s 'phpbee build failed' alex@kochetov.com
	echo $PHPUNIT
	exit 1;
fi
