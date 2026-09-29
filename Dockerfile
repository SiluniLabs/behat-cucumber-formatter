# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# The optional "ca_cert" secret (PEM) is trusted for networks intercepting HTTPS behind a corporate proxy.
RUN --mount=type=secret,id=ca_cert \
    if [ -s /run/secrets/ca_cert ]; then \
        cp /run/secrets/ca_cert /usr/local/share/ca-certificates/extra-ca.crt && update-ca-certificates; \
    fi \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pcov

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app
