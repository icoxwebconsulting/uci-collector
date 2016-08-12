# bitgandtter webimage
FROM        icox/symfony:php7

# basic env fix
ENV         TERM xterm

# install packages
RUN         apt-get install -y php7.0-phpdbg

ENV         SYMFONY_ENV dev

# mount
VOLUME      /var/www
WORKDIR     /var/www

EXPOSE      80

CMD         service php7.0-fpm start && nginx
