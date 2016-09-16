FROM php:latest
MAINTAINER Hendrik Maus "aidentailor@gmail.com"

# Tell branda it is running inside a docker container
ENV BRANDA_ENVIRONMENT=docker

# Get all dependencies to install drafter and composer
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get clean -y && \
    apt-get install -y \
        python-pip \
        git \
        tar \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
        libbz2-dev \
        libxslt-dev \
        libldap2-dev \
        php-pear \
        curl \
        git \
        subversion \
        unzip \
        wget && \
    apt-get autoclean -y && \
    apt-get autoremove -y

# PHP Extensions
RUN docker-php-ext-install bcmath mcrypt zip bz2 mbstring pcntl xsl \
  && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  && docker-php-ext-install gd \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
  && docker-php-ext-install ldap

# Setup the Composer installer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"

# Install Composer
RUN php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer && rm -rf /tmp/composer-setup.php

# Allow Composer to be run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer --version

# Setup branda's living room
RUN mkdir -p /opt/branda
COPY ./ /opt/branda
WORKDIR /opt/branda
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Setup appdata guest room
# people will mount their project here
RUN mkdir -p /appdata
VOLUME ["/appdata"]

# Set up the command arguments
CMD ["-"]
ENTRYPOINT ["bin/branda", "--ansi"]

# Usage
#  docker run -it --name "branda" --rm -p 8000:8000 \
#    -v $(pwd):/appdata hendrikmaus/branda \
#    mock 0.0.0.0 -f /appdata/your-service.apib

