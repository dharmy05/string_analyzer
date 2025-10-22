# Use official PHP with Apache
FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# Copy all project files into Apache root
WORKDIR /var/www/html
COPY . /var/www/html

# Give proper permissions (important for SQLite DB)
RUN chown -R www-data:www-data /var/www/html

# Expose web port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
