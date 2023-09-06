FROM php:8.2-cli

RUN apt update && apt install -y libfreetype-dev libjpeg62-turbo-dev libpng-dev libzip-dev sudo
RUN docker-php-ext-install gd zip pcntl
RUN yes '' | pecl install -o -f redis &&  rm -rf /tmp/pear &&  docker-php-ext-enable redis
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
