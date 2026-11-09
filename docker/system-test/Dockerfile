FROM php@sha256:5249d94b4b00db63a0ed53e6528114926110ab5dcb0d0f07cc30d1ea7dbe747e

RUN set -eux; \
    sed -i 's|http://deb.debian.org/debian|https://archive.debian.org/debian|g' /etc/apt/sources.list; \
    sed -i 's|http://security.debian.org/debian-security|https://archive.debian.org/debian-security|g' /etc/apt/sources.list; \
    sed -i '/stretch-updates/d' /etc/apt/sources.list; \
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid-until

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libicu-dev \
        g++ \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl mbstring

WORKDIR /app
