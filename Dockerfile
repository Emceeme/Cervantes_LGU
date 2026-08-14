FROM php:8.2-apache

# Install dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install mysqli, PDO MySQL, and PDO PostgreSQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set Apache document root
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80