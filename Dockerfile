# robotschool_academy — PHP 8.1 + Apache
# Imagen sirve la app desde /var/www/html en el puerto 80.
# Convive con el stack RobotSchool detrás de robotschool_nginx (no expone puertos al host).

FROM php:8.1-apache

# -- Paquetes del sistema para extensiones PHP --
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        zip \
        unzip \
        git \
        curl \
    && rm -rf /var/lib/apt/lists/*

# -- Extensiones PHP --
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        mbstring \
        intl \
        gd \
        zip \
        opcache

# -- Apache: mod_rewrite + esconder versión --
RUN a2enmod rewrite headers \
    && { \
        echo 'ServerTokens Prod'; \
        echo 'ServerSignature Off'; \
        echo 'ServerName academy.local'; \
    } > /etc/apache2/conf-available/security-extra.conf \
    && a2enconf security-extra

# -- PHP production config --
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 20M'; \
        echo 'post_max_size = 25M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 60'; \
        echo 'date.timezone = America/Bogota'; \
    } > "$PHP_INI_DIR/conf.d/zz-rsal.ini"

# -- OPcache tuning para prod --
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > "$PHP_INI_DIR/conf.d/zz-opcache.ini"

# -- Copiar la app --
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html/

# -- Crear uploads/ writable y montable --
RUN mkdir -p /var/www/html/uploads/cursos \
             /var/www/html/uploads/comprobantes \
             /var/www/html/uploads/estudiantes \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

# Apache foreground (default del base image)
CMD ["apache2-foreground"]
