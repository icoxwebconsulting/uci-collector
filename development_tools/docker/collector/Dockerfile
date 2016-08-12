# bitgandtter webimage
FROM        icox/symfony:php7

# basic env fix
ENV         TERM xterm

# copy all
ADD         .   /var/www

# remove unnecesary files
RUN         rm /var/www/framework/web/app_dev.php
RUN         rm /var/www/framework/web/config.php

# update dependencies
RUN         bash composer-update.sh -p
RUN         cd framework && SYMFONY_ENV=prod bin/console cache:warmup
RUN         chmod -R 777 framework/var/

ENV         SYMFONY_ENV prod

# mount
WORKDIR     /var/www

EXPOSE      80

CMD         service php7.0-fpm start && nginx
