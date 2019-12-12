FROM php:7.2-apache

RUN apt-get update && apt-get install -y zlib1g-dev mariadb-client \
	&& docker-php-ext-install mysqli pdo_mysql zip
