FROM php:8.2-apache

# Install Moodle required PHP extensions and git
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
        opcache \
        xml \
        soap \
        exif \
    && a2enmod rewrite

# Configure PHP settings for Moodle
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_vars=5000'; \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
} > /usr/local/etc/php/conf.d/moodle-php.ini

# Clone Moodle Core (v4.4 LTS Stable) into /var/www/html
RUN git clone --depth 1 --branch MOODLE_404_STABLE https://github.com/moodle/moodle.git /tmp/moodle \
    && cp -rn /tmp/moodle/* /var/www/html/ \
    && rm -rf /tmp/moodle \
    && mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/html /var/www/moodledata

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
