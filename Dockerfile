FROM php:8.2-apache

# Cache bust - v4
RUN echo "Build v4"

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PDO PostgreSQL extension
RUN docker-php-ext-install pdo pdo_pgsql mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set Apache document root
WORKDIR /var/www/html

# Install Composer dependencies (ignore all platform requirements)
RUN composer update --no-dev --optimize-autoloader --ignore-platform-reqs

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Create upload directories with proper permissions
RUN mkdir -p /var/www/html/lgu/uploads/news \
    /var/www/html/lgu/uploads/scholarship \
    /var/www/html/lgu/uploads/resumes \
    /var/www/html/lgu/uploads/procurement \
    /var/www/html/uploads/mswd_documents \
    && chown -R www-data:www-data /var/www/html/lgu/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/lgu/uploads \
    && chmod -R 750 /var/www/html/uploads

# Create .htaccess to protect uploads directory
RUN echo "Deny from all" > /var/www/html/uploads/.htaccess

EXPOSE 80