FROM php:8.4-cli

# Copy custom PHP configuration
COPY custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini

# Set working directory
WORKDIR /app

# Copy backend composer files only to improve build caching
COPY backend/composer.json backend/composer.lock ./backend/

# Install system dependencies, download Composer, and install backend dependencies
RUN apt-get update && apt-get install -y unzip git libzip-dev && \
    docker-php-ext-install zip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    cd backend && composer install --no-dev --prefer-dist

# Copy the rest of the application
COPY . .

EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080}"]
