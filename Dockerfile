FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx

# Shared PHP configuration: errors to the container log instead of into the
# response, no X-Powered-By, and date.timezone (the stock image defaults to
# UTC). No ENV TZ here because there is no cron in this image. See php.ini.
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY html/ /var/www/html/

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
