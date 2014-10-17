#!/bin/sh
REPO="file:///home/www/phpbee.org/svn/phpbee.org"
LASTFNAME="public_html/download/`ls -tr public_html/download/ | tail -n1`";
MTIME=`stat -c '%z' $LASTFNAME`
LASTDATE=`date -d "$MTIME" "+%Y-%m-%d"  `

FNAME="phpbee-`date "+%d%b%y"`.zip"
echo $FNAME


rm -fr build_test
mkdir build_test
cd build_test
svn export $REPO . --force

./install.sh

cp build.config.php config.php

php public_html/install.php install_key=12345
cd tests

PHPUNIT=`phpunit run.php`
if [ "$?" -ne "0" ]
then	
	echo $PHPUNIT | mail -s 'phpbee build failed' alex@kochetov.com andrey.pahomov@gmail.com
	echo $PHPUNIT
	exit 1;
fi

cd ../..

rm -fr build
mkdir build
cd build
svn export $REPO . --force

svn log -r '{'$LASTDATE'}':'{'`date -d tomorrow "+%Y-%m-%d"`'}' --xml --verbose $REPO > Changelog.xml
xsltproc ../svn2cl.xsl Changelog.xml  > Changelog.txt
xsltproc ../svn2html.xsl Changelog.xml  > Changelog.html


./install.sh


#php phar.php
find . -name public_html -mindepth 2 -exec sh -c "L=\`dirname {}\`; mkdir -p public_html/\$L; cp -r {}/* public_html/\$L ; " \;
#rm -fr libs
phpdoc -d libs -i smarty/ -t public_html/phpdoc



zip -r phpbee.zip config.php html modules packages public_html Changelog.txt
#zip -r phpbee.zip gs_libs.phar.gz 
zip -r phpbee.zip libs 
zip phpbee.zip var
mv phpbee.zip ..
cd ..
cp build/Changelog.html public_html/download/Changelog-$FNAME.html
cp phpbee.zip public_html/download/$FNAME

php public_html/index.php downloads/submit_phpbee file=public_html/download/$FNAME xml=build/Changelog.xml

#rm -fr build
#rm -fr build_test
echo $FNAME > html/last_build.html

echo "Build $FNAME created and uploaded" 
echo "Build $FNAME created and uploaded" | mail -s 'phpbee build completed' alex@kochetov.com andrey.pahomov@gmail.com
