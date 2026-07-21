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
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/server-name.conf \
    && a2enconf server-name \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY assets/website/simplewebsite/ /var/www/html/
COPY docker/website/php.ini /usr/local/etc/php/conf.d/zz-cabal.ini
COPY docker/website/apache.conf /etc/apache2/conf-available/cabal-website.conf

RUN rm -f /var/www/html/phpinfo.php /var/www/html/dashboard/phpinfo.php \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 0755 {} + \
    && find /var/www/html -type f -exec chmod 0644 {} + \
    && a2enconf cabal-website
