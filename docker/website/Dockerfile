FROM php:8.3-apache-bookworm

ARG TZ=Asia/Shanghai
ENV TZ=${TZ}

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        tzdata \
        unixodbc-dev \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/microsoft-prod.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && ln -snf "/usr/share/zoneinfo/${TZ}" /etc/localtime \
    && echo "${TZ}" > /etc/timezone \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY assets/website/CabalWebsiteForFree/ /var/www/html/
COPY docker/website/php.ini /usr/local/etc/php/conf.d/zz-cabal.ini

RUN chown -R www-data:www-data /var/www/html
