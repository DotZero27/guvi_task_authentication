FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && \
    apt-get install -y \
    default-mysql-client \
    libssl-dev \
    pkg-config \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) mysqli pdo_mysql gd zip

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./composer.json ./
RUN composer update --ignore-platform-req=ext-mongodb

# Enable Apache modules
RUN a2enmod rewrite

# Copy application files into container
COPY . /var/www/html/

# Expose port 80
EXPOSE 80

# Add entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
