# VNL test imajı: resmi PHP CLI + pdo_mysql.
#
#   docker compose -f compose.test.yml run --rm test
#
# Resmi php:*-cli imajında pdo_mysql yok; db testleri onsuz atlanır.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

RUN docker-php-ext-install pdo_mysql

WORKDIR /app
