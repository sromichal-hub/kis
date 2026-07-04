FROM php:8.2-cli

# Install system deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    default-mysql-client \
    netcat-openbsd \
  && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock* /app/

# Install PHP dependencies (this runs at image build time)
RUN if [ -f composer.lock ]; then composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; else composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true; fi

# Copy application files
COPY . /app

# Make entrypoint executable
RUN chmod +x /app/docker-entrypoint.sh /app/bin/console || true

EXPOSE 8000

CMD ["/bin/bash","-c","./docker-entrypoint.sh"]

