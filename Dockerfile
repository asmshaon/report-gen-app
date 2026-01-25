FROM php:5.6-cli-alpine

# Install dependencies
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# Configure gd extension before install
RUN docker-php-ext-configure gd \
    --with-freetype-dir=/usr/include/ \
    --with-jpeg-dir=/usr/include/ \
    --with-png-dir=/usr/include/

# Install extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    intl \
    opcache \
    mbstring \
    gd

# Install Composer (kept for future use, but mPDF will be installed manually)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Download and install mPDF manually (supports PHP 5.6)
RUN apk add --no-cache wget && \
    cd /tmp && \
    wget https://github.com/mpdf/mpdf/archive/refs/tags/v7.1.9.tar.gz && \
    tar -xzf v7.1.9.tar.gz && \
    mkdir -p /var/www/html/lib && \
    mv mpdf-7.1.9 /var/www/html/lib/mpdf && \
    rm v7.1.9.tar.gz && \
    apk del wget

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Expose port (if using built-in server)
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000"]
