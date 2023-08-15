from php:8.0-fpm-alpine

run ln -s $PHP_INI_DIR/php.ini-$mode $PHP_INI_DIR/php.ini  \
        && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
        && docker-php-ext-install pdo_mysql sockets
