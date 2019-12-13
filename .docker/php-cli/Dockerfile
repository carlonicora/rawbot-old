FROM php:7.3.9-cli-alpine

ARG BUILD_ENVIRONMENT
ARG XDEBUG_PORT
ARG XDEBUG_HOST
ARG USER
ARG GROUP
ARG WORKERS

COPY ./shell/ /tmp/scripts/
RUN chmod -R 777 /tmp/scripts/

COPY ./php-cli/configurations/*.ini /usr/local/etc/php/conf.d/

RUN apk update \
    && apk add --no-cache curl g++ make autoconf bash supervisor

RUN if [ "${BUILD_ENVIRONMENT}" = "development" ]; then \
    docker-php-source extract \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-source delete \
    && /tmp/scripts/distributeConfig.sh /usr/local/etc/php/conf.d/zz-xdebug.ini "{XDEBUG_PORT}" "${XDEBUG_PORT}" \
    && /tmp/scripts/distributeConfig.sh /usr/local/etc/php/conf.d/zz-xdebug.ini "{XDEBUG_HOST}" "${XDEBUG_HOST}"; \
fi

RUN docker-php-ext-install mysqli \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./shell/configurations/supervisord.conf /etc/supervisord.conf
RUN /tmp/scripts/distributeConfig.sh /etc/supervisord.conf "{WORKERS}" "${WORKERS}";

RUN rm -rf /tmp/*

RUN addgroup -g 1000 -S developers && \
    adduser -u 1000 -S developer -G developers