# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable Apache rewrite module (needed for Laravel/Slim routing)
RUN a2enmod rewrite

# Copy Apache virtual host configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Install PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy all project files into the container
COPY . /var/www/html/

# Install Composer + dependencies
RUN apt-get update && apt-get install -y unzip git curl ca-certificates && \
    curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-dev --optimize-autoloader && \
    rm composer-setup.php && apt-get clean && rm -rf /var/lib/apt/lists/*

# Expose Apache
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
