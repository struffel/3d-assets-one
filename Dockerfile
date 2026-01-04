FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
	libpng-dev \
	libjpeg-dev \
	libfreetype6-dev \
	libzip-dev \
	libmagickwand-dev \
	sqlite3 \
	libsqlite3-dev \
	unzip \
	git \
	curl \
	&& rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) \
	gd \
	zip \
	mysqli \
	calendar

# Install ImageMagick PHP extension
RUN pecl install imagick \
	&& docker-php-ext-enable imagick

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure PHP settings
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory-limit.ini \
	&& echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/upload.ini \
	&& echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/upload.ini \
	&& echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini \
	&& echo "max_input_vars = 3000" >> /usr/local/etc/php/conf.d/input.ini

# Enable Apache modules
RUN a2enmod rewrite ssl headers

# Set proper permissions
RUN mkdir -p /var/www/acg/ \
	&& chown -R www-data:www-data /var/www/acg/ \
	&& chmod -R 755 /var/www/acg/

# Expose port 80
EXPOSE 80

# Set the document root and start Apache
CMD ["/bin/bash", "-c", "sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf && apache2-foreground"]