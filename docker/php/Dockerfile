# PHP-FPM untuk SmartCity Attendance.
# Versi saat ini: PHP 8.5 (aplikasi membutuhkan PHP >= 8.1, lihat README).
FROM php:8.5-fpm-alpine

# Ekstensi yang dibutuhkan aplikasi (lihat app/Controllers/InstallController.php):
#   - pdo_mysql : koneksi ke MariaDB/MySQL (App\Core\Database memakai PDO)
#   - zip       : ekspor Excel (.xlsx) dan ZIP sertifikat; CSV tetap jalan tanpanya
#   - gd        : membaca PNG/JPG desain, logo, tanda tangan dan stempel sertifikat
# mbstring, fileinfo, OPcache, pdo_sqlite, session, dll. sudah termasuk di image resmi.
#
# Catatan: docker-php-ext-install dijalankan satu per satu. Mengompilasi beberapa
# ekstensi sekaligus dalam satu perintah pernah gagal di image ini.
RUN apk add --no-cache libpng libjpeg-turbo libzip \
    && apk add --no-cache --virtual .build-deps libpng-dev libjpeg-turbo-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql \
    && docker-php-ext-install -j"$(nproc)" zip \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd \
    && apk del --no-network .build-deps

# Konfigurasi produksi (display_errors=Off, expose_php=Off, limit upload, OPcache).
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/zz-smartcity.ini"

# Entrypoint: membuat config/config.php dari environment variable lalu menjalankan php-fpm.
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/php/generate-config.php /usr/local/bin/generate-config.php
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www/html
EXPOSE 9000
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
