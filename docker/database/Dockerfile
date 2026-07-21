FROM mcr.microsoft.com/mssql/server:2022-CU18-ubuntu-22.04

USER root

ARG TZ=Asia/Shanghai
ENV TZ=${TZ}

RUN apt-get update \
    && apt-get install -y --no-install-recommends tzdata \
    && ln -snf "/usr/share/zoneinfo/${TZ}" /etc/localtime \
    && echo "${TZ}" > /etc/timezone \
    && rm -rf /var/lib/apt/lists/*

COPY docker/database/restore.sh /usr/local/bin/cabal-db-restore
COPY docker/database/website-init.sql /usr/local/share/cabal/website-init.sql
RUN chmod 0755 /usr/local/bin/cabal-db-restore

USER root

CMD ["/opt/mssql/bin/sqlservr"]
