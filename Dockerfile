# Stage 1: Build Frontend Assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Final Production Container
FROM php:8.3-cli-alpine
WORKDIR /var/www/html

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Copy built frontend assets from stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions for Laravel directories
RUN chmod -R 777 storage bootstrap/cache

# Create SQLite database directory
RUN mkdir -p database && touch database/database.sqlite && chmod -R 777 database

# Run entry point commands and serve
EXPOSE 8000
CMD cp -n .env.example .env && sed -i '/GEMINI_API_KEY=/d' .env && php artisan key:generate --no-interaction && php artisan config:clear && php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000

