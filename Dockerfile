FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx

# Errors to the log not the response, no X-Powered-By, date.timezone. No ENV TZ:
# no cron in this image. See php.ini.
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY html/ /var/www/html/

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
