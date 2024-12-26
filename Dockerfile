# Use an official PHP runtime as a parent image
FROM php:8.2-apache

# Install necessary extensions
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . .

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Expose port 8005 to the outside world
EXPOSE 8005

# Set the command to run Apache in the background
CMD ["apache2-foreground"]
