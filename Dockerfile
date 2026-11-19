# Image du serveur web : Apache + PHP 8.2, avec les extensions PDO et pdo_mysql
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql