# bitgandtter webimage
FROM        ubuntu:16.04

# basic env fix
ENV         TERM xterm

# install packages
RUN         apt-get update && apt-get dist-upgrade -y
RUN         apt-get install -y apt-utils
RUN         apt-get install -y htop nano curl git zip unzip
RUN         apt-get install -y nginx php7.0-common php7.0-cli php7.0-fpm php7.0-mcrypt php7.0-gd \
            php7.0-curl php7.0-intl php7.0-mysql php7.0-xml php7.0-mbstring php7.0-dev php7.0-zip
            php-pear libcurl4-openssl-dev pkg-config libssl-dev libsslcommon2-dev wkhtmltopdf xvfb
RUN         pecl install mongodb
RUN         echo "extension=mongodb.so" >> /etc/php/7.0/mods-available/mongodb.ini
RUN         phpenmod mongodb

RUN         curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# configure services
ADD         nginx.conf /etc/nginx/
ADD         src.conf /etc/nginx/sites-available/src.conf
ADD         src.ini /etc/php/7.0/fpm/conf.d/
ADD         src.ini /etc/php/7.0/cli/conf.d/
ADD         pool.conf /etc/php/7.0/fpm/pool.d/

RUN         ln -s /etc/nginx/sites-available/src.conf /etc/nginx/sites-enabled/src
RUN         rm /etc/nginx/sites-enabled/default

# cleaning packages
RUN         apt-get purge -y php7.0-dev php-pear libcurl4-openssl-dev pkg-config libssl-dev libsslcommon2-dev
RUN         apt-get autoremove -y
RUN         rm -rf /var/cache/apt/archives/*

WORKDIR     /var/www

EXPOSE      80

CMD         service php7.0-fpm start && nginx
