FROM php:8.2-apache

# Install dependencies for PostgreSQL and Composer
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install mysqli, PDO MySQL, and PDO PostgreSQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set Apache document root
WORKDIR /var/www/html

# Install Composer dependencies
RUN composer update --no-dev --optimize-autoloader

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