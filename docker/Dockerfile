FROM php:7.3-apache

RUN apt-get update && apt-get install -y zlib1g-dev libzip-dev mariadb-client \
	&& docker-php-ext-install mysqli pdo_mysql zip

RUN curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar --output /usr/bin/wp \
	&& chmod +X /usr/bin/wp && chmod 766 /usr/bin/wp