# VNL test imajı: resmi PHP CLI + veritabanı sürücüleri.
#
#   docker compose -f compose.test.yml run --rm test           # MySQL
#   docker compose -f compose.test.yml run --rm test-pgsql     # PostgreSQL
#   docker compose -f compose.test.yml run --rm test-sqlsrv    # SQL Server
#   docker compose -f compose.test.yml run --rm test-sqlite    # SQLite
#
# Resmi php:*-cli imajında pdo_mysql yok; db testleri onsuz atlanır.
# DB_DRIVERS hangi PDO sürücülerinin kurulacağını söyler (boşlukla ayrık):
# mysql pgsql sqlsrv sqlite. Varsayılan yalnız mysql — CI'daki MySQL işi
# Microsoft ODBC sürücüsünü derlemek için beklemesin.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

ARG DB_DRIVERS="mysql"

RUN set -eux; \
    for driver in ${DB_DRIVERS}; do \
        case "$driver" in \
            mysql) docker-php-ext-install pdo_mysql ;; \
            pgsql) \
                apt-get update; \
                apt-get install -y --no-install-recommends libpq-dev; \
                docker-php-ext-install pdo_pgsql ;; \
            sqlite) php -m | grep -qi '^pdo_sqlite$' ;; \
            sqlsrv) \
                # pdo_sqlsrv, Microsoft'un ODBC sürücüsüne (msodbcsql18) dayanır.
                apt-get update; \
                # Depo tanımı ve anahtarı Microsoft'un paketinden gelir (Debian
                # sürümüne uygun anahtar; trixie eski anahtarı reddeder).
                apt-get install -y --no-install-recommends curl ca-certificates; \
                . /etc/os-release; \
                curl -fsSL -o /tmp/packages-microsoft-prod.deb \
                    "https://packages.microsoft.com/config/debian/${VERSION_ID%%.*}/packages-microsoft-prod.deb"; \
                dpkg -i /tmp/packages-microsoft-prod.deb; \
                rm /tmp/packages-microsoft-prod.deb; \
                apt-get update; \
                ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev; \
                pecl install pdo_sqlsrv; \
                docker-php-ext-enable pdo_sqlsrv ;; \
            *) echo "bilinmeyen surucu: $driver" >&2; exit 1 ;; \
        esac; \
    done; \
    rm -rf /var/lib/apt/lists/* /tmp/pear

WORKDIR /app
