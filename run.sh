#!/bin/bash
/usr/sbin/php-fpm
chmod -R 777 /var/run/php-fpm

cd /var/www/service && php vendor/ustudio/service_mandatory/ConfigServiceController.php
php /var/www/service/init --env=Production --overwrite=y

#php /var/www/service/yii migrate --interactive=0


# Run daemon
if [ -z "$NO_JAVA" ]; then
    /usr/sbin/nginx
    java -Xmx128m -jar /maven/prana-plus.jar
else
    /usr/sbin/nginx -g 'daemon off;'
fi