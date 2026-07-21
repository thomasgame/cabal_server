FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        brotli \
        bzip2 \
        ca-certificates \
        curl \
        libcurl4 \
        libgcc-s1 \
        libstdc++6 \
        openssl \
        procps \
        python3 \
        supervisor \
        tdsodbc \
        tzdata \
        unixodbc \
        zlib1g \
    && rm -rf /var/lib/apt/lists/*

COPY assets/game/runtime-libs/ /usr/local/lib/
COPY assets/game/runtime-hooks/ /usr/lib/cabal/
COPY assets/game/hook-deps/ /usr/lib/cabal/
COPY assets/game/bin/ /etc/cabal_bin/
COPY assets/game/scripts/ /etc/cabal_scripts/
COPY assets/game/structure/ /etc/cabal_structure/
COPY assets/game/config/ /opt/cabal-seed/config/
COPY docker/game/supervisord.conf /etc/supervisord.conf
COPY docker/game/entrypoint.sh /usr/local/bin/cabal-entrypoint
COPY docker/game/game-control.sh /usr/local/bin/game-control
COPY docker/game/healthcheck.sh /usr/local/bin/cabal-healthcheck

RUN printf '%s\n' '/usr/lib/cabal' > /etc/ld.so.conf.d/cabal.conf \
    && ln -sf /usr/local/lib/libcryptopp.so.8.1.0 /usr/local/lib/libcryptopp.so.8 \
    && ln -sf /etc/cabal_scripts/odbc/odbc.ini /etc/odbc.ini \
    && ln -sf /etc/cabal_scripts/odbc/odbcinst.ini /etc/odbcinst.ini \
    && mkdir -p \
        /etc/cabal \
        /etc/cabal_etc/core \
        /var/log/cabal \
        /var/log/supervisor \
    && chmod 0755 /usr/local/bin/cabal-entrypoint /usr/local/bin/game-control /usr/local/bin/cabal-healthcheck \
    && chmod 0700 /etc/cabal_scripts/*.sh \
    && chmod 0700 /etc/cabal_bin/* \
    && ldconfig

EXPOSE 35001-35100

HEALTHCHECK --interval=30s --timeout=10s --start-period=240s --retries=3 \
    CMD ["/usr/local/bin/cabal-healthcheck"]

ENTRYPOINT ["/usr/local/bin/cabal-entrypoint"]
STOPSIGNAL SIGTERM
