# -----------------------------------------------------------
#  [*] Apache — production image
#
#  The image the cyberphish-apache container runs: PHP
#  8.4.27 under Apache from the official php image, with
#  the pdo_mysql, gd (JPEG and FreeType, for the
#  certificate) and zip extensions, mod_rewrite for the
#  front controller of public/.htaccess and mod_remoteip
#  for the visitor's address behind the endpoint. Apache
#  listens on port 8080 (ports.conf) with the virtual host
#  of 000-default.conf. The app goes to /var/www/html,
#  where composer installs its vendor without the
#  development packages; public/upload, where the pages
#  link the uploads, becomes a link to storage/app/public,
#  where the panel stores them. php.ini is PHP's production
#  file with expose_php off, so no response names PHP's
#  version, with room for uploads of 10 MB, and with a
#  disable_functions list that takes away the functions
#  that start programs and readfile, symlink and link,
#  while the ones the app needs stay — PhpRuntimeTest
#  (app/tests/Platform) pins both lists. The image runs as
#  user 1000, as docker-compose.yml runs the container too.
#  The build context is this folder; .dockerignore keeps
#  the test suite out.
#
#  Used by:
#    - pushDockerhub.sh (this folder) — builds it and
#      publishes it as vuknf/cyberphish-apache, the image
#      docker-compose.yml runs
#    - runTests.sh (this folder) — builds it as
#      cyberphish-apache-check, the base Dockerfile.tests
#      adds the suite to
# -----------------------------------------------------------
FROM php:8.4.27-apache

# MySQL deps
RUN docker-php-ext-install pdo_mysql


# Install GD (the image is Debian trixie: FreeType's headers come in
# libfreetype-dev, and since PHP 7.4 gd takes --with-freetype/--with-jpeg)
RUN apt-get update
RUN apt-get install -y build-essential libssl-dev zlib1g-dev libpng-dev libjpeg-dev libfreetype-dev libzip-dev
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd
RUN docker-php-ext-install zip





# Apache
RUN a2enmod rewrite remoteip

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


# --- Hide PHP's version: php.ini-production leaves expose_php on, which
# puts X-Powered-By with the exact version on every response
RUN sed -i 's/^expose_php = On$/expose_php = Off/' "$PHP_INI_DIR/php.ini"


# --- Uploads: php.ini-production takes files of 2 MB, while most of the
# course's lesson files weigh 2 to 5.4 MB, and PHP drops a larger one
# before the app sees it ("No file was uploaded!"); the post around the
# file needs room above it
RUN sed -i -e 's/^upload_max_filesize = .*/upload_max_filesize = 10M/'          \
           -e 's/^post_max_size = .*/post_max_size = 12M/' "$PHP_INI_DIR/php.ini"



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


# Code evaluation cannot be switched off here, so the list names none of it:
# eval, include and include_once are language constructs, which
# disable_functions does not reach, and create_function is gone since PHP 8.0.
# assert stays enabled too: it can no longer evaluate code since PHP 8 (string
# assertions are gone), and PHP 8 turns a disabled function into an undefined
# one, so every assert() in the vendor's libraries (Symfony, ramsey/uuid,
# brick/math ...) would be a fatal error. zend.assertions = -1 of
# php.ini-production compiles those calls away anyway.
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
