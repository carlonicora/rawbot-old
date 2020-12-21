FROM php:7.4-cli-alpine as base

RUN set -xe \
        && apk add --no-cache --virtual .build-deps \
            $PHPIZE_DEPS \
            bash \
            git \
            curl \
            supervisor \
            g++ \
            libzip-dev \
            libevent-dev \
            openssl-dev \
            make \
            autoconf;

COPY ./php-cli/configurations/*.ini /usr/local/etc/php/conf.d/
COPY ./shell/ /tmp/scripts/

RUN docker-php-ext-install mysqli bcmath sockets zip \
    && addgroup -g 1000 -S dockeruser \
    && adduser -u 1000 -S dockeruser -G dockeruser \
    && touch /var/log/supervisord.log \
    && chown -R dockeruser:dockeruser /var/log/supervisord.log \
    && chmod -R 777 /tmp/scripts/

RUN pecl install event \
    && docker-php-ext-enable --ini-name zz-event.ini event \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM base as prd
ARG WORKERS

COPY ./shell/configurations/supervisord.conf /etc/supervisord.conf
RUN /tmp/scripts/distributeConfig.sh /etc/supervisord.conf "{WORKERS}" "${WORKERS}" \
    && rm -rf /tmp/*
CMD ["/usr/bin/supervisord","--configuration=/etc/supervisord.conf"]

FROM base as dev
ARG XDEBUG_PORT
ARG XDEBUG_HOST

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && /tmp/scripts/distributeConfig.sh /usr/local/etc/php/conf.d/zz-xdebug.ini "{XDEBUG_PORT}" "${XDEBUG_PORT}" \
    && /tmp/scripts/distributeConfig.sh /usr/local/etc/php/conf.d/zz-xdebug.ini "{XDEBUG_HOST}" "${XDEBUG_HOST}" \
    && docker-php-source delete \
    && rm -rf /tmp/*