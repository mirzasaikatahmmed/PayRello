FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    default-mysql-client \
    git \
    && rm -rf /var/lib/apt/lists/*

# Build Imagick from GitHub (avoids PECL download corruption issues)
RUN cd /tmp \
    && git clone https://github.com/Imagick/imagick.git --depth 1 --branch 3.7.0 imagick \
    && cd imagick \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable imagick \
    && rm -rf /tmp/imagick

# Configure GD with JPEG, WebP, FreeType support
RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-webp \
    --with-freetype

# Install PHP extensions not pre-compiled in the base image
# (curl, json, openssl, tokenizer, fileinfo are already built-in to php:8.2-apache)
RUN docker-php-ext-install \
    pdo_mysql \
    gd \
    zip \
    mbstring \
    bcmath \
    intl \
    opcache

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy Apache virtual host config
COPY docker/apache/payrello.conf /etc/apache2/sites-available/000-default.conf

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/payrello.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create storage directory and set permissions
RUN mkdir -p /var/www/html/pp-media/storage \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/pp-media

# Copy and set executable entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
