FROM --platform=linux/amd64 php:8.5-cli-alpine

RUN apk add --no-cache libstdc++ \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install openswoole-26.2.0 \
    && docker-php-ext-enable openswoole \
    && apk del .build-deps

WORKDIR /app

COPY ./ /app/

EXPOSE 9999

CMD ["php", "-d", "opcache.enable_cli=1", "-d", "opcache.jit=tracing", "-d", "opcache.jit_buffer_size=16M", "/app/server.php"]
