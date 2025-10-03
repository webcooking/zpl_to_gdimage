FROM php:8.2-cli

# Install system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    wget \
    libmagickwand-dev \
    imagemagick \
    librsvg2-bin \
    librsvg2-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

# Install GD extension dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    librsvg2-dev \
    pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Default command
CMD ["php", "examples/test_all.php"]
