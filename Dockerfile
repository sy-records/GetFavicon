FROM php:8.3-apache

WORKDIR /var/www/html

COPY api /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
