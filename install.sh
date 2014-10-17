#!/bin/sh

mv default.config.php config.php
mkdir  var
chmod 777 config.php var modules
mv html/index_page_default.html html/index.html
mv html/404_default.html html/404.html
cp public_html/worker.php public_html/index.php

mkdir public_html/files
chmod 777 public_html/files

