FROM php:7.2.24-apache

# MySQL deps
RUN docker-php-ext-install pdo_mysql


# Switch to archived Debian Buster repositories (EOL)
RUN sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list && \
    sed -i 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' /etc/apt/sources.list && \
    sed -i '/buster-updates/d' /etc/apt/sources.list


# Install GD
RUN apt-get update
RUN apt-get install -y build-essential libssl-dev zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/     \
    && docker-php-ext-install gd
RUN docker-php-ext-install zip





# Apache
RUN a2enmod rewrite

# Install composer and its dependencies
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer





# SETUP WEB APP
# =============
WORKDIR /var/www/html

# Configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ports.conf /etc/apache2/ports.conf


# Web application
COPY --chown=1000:1000 app /var/www/html
RUN ln -sfn /var/www/html/storage/app/public /var/www/html/public/upload

# Install vendor dependencies
RUN APP_URL=http://localhost composer install --no-dev --optimize-autoloader









# +-------------------------------+
# +----------- php.ini -----------+
# +-------------------------------+

# --- Initiate php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"



# --- Initialize disable_functions
# Necessary PHP functions:
# unlink - for downloading certificate of completion
# mkdir - for simulations self evaluations (and probably rmdir)


RUN sed -i 's/^disable_functions =.*/disable_functions = funcAbc/' "$PHP_INI_DIR/php.ini"


# Dangerous functions for executing system commands:
RUN echo "exec,passthru,system,shell_exec,proc_open,popen,pcntl_exec"           | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"


# Dangerous functions for file system access:
RUN echo "readfile,symlink,link"                             | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"
#         RUN echo "fopen,fclose,fwrite,fread,file_get_contents,file_put_contents"        | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.i>


# Dangerous functions for code evaluation:
RUN echo "eval,assert,create_function,include,include_once"    |     xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"
#         RUN echo "preg_replace,include_once"        | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"
#         RUN echo "require,require_once"                                                 | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"


# Dangerous functions for network operations:
#         RUN echo "fsockopen,pfsockopen,stream_socket_client,curl_exec,curl_multi_exec"  | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"


# Dangerous functions for serialization:
#         RUN echo "serialize,unserialize"                                                | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"


# Other potentially dangerous functions:
#         RUN echo "base64_encode,base64_decode" | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"
#         RUN echo "set_time_limit,header,session_start,ini_set,putenv,dl"                | xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"



#         RUN echo ""    |     xargs -I {} sed -i "s/^\(disable_functions =.*\)/\1,{}/" "$PHP_INI_DIR/php.ini"

# +-------------------------------+
# +-------------------------------+
# +-------------------------------+




USER 1000:1000
