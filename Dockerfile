FROM php:8.2-apache

# GD (CAPTCHA) සහ Database extensions install කිරීම
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli

# Apache mod_rewrite Active කිරීම
RUN a2enmod rewrite

# Apache Permission Fix (403 Forbidden සදාකාලිකව ඉවත් කිරීමට)
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override-permissions.conf \
    && a2enconf override-permissions

# Project එක COPY කිරීම
COPY . /var/www/html/

# Permissions සකස් කිරීම
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# Soap සඳහා අවශ්‍ය libxml2-dev packages install කිරීම සහ php soap extension එක enable කිරීම
RUN apt-get update && apt-get install -y libxml2-dev \
    && docker-php-ext-install soap