#!/bin/sh
   case "$1" in
   start)
     echo "starting VD Logger..."
     /home/www/phpbee.org/stats/bee_logger.php 
     ;;
   restart)
     echo "restarting VD Logger..."
     kill -HUP `cat /var/run/bee_logger.pid` 
     /home/www/phpbee.org/stats/bee_logger.php
     ;;
   stop)
     echo "stopping VD Logger..."
     kill -1 `cat /var/run/bee_logger.pid`
     rm /var/run/bee_logger.pid 
     ;;
   *)
     echo "Usage: $0 {start|stop|restart}"
     exit 1
     ;;
esac
