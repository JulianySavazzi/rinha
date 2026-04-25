FROM php:8.5-cli-alpine

RUN apk add --no-cache libstdc++ \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install openswoole-26.2.0 \
    && docker-php-ext-enable openswoole \
    && apk del .build-deps

WORKDIR /app

COPY ./ /app/

EXPOSE 9999

CMD ["php", "/app/server.php"]
