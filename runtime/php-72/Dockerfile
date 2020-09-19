FROM ymir/runtime/base as php-build


ENV PHP_BUILD_DIR=${BUILD_DIR}/php
RUN set -xe; \
    mkdir -p ${PHP_BUILD_DIR}; \
# Download and upack the source code
    curl -Ls https://github.com/php/php-src/archive/php-7.2.33.tar.gz \
  | tar xzC ${PHP_BUILD_DIR} --strip-components=1
# Move into the unpackaged code directory
WORKDIR  ${PHP_BUILD_DIR}/

# Configure the build
# -fstack-protector-strong : Be paranoid about stack overflows
# -fpic : Make PHP's main executable position-independent (improves ASLR security mechanism, and has no performance impact on x86_64)
# -fpie : Support Address Space Layout Randomization (see -fpic)
# -O3 : Optimize for fastest binaries possible.
# -I : Add the path to the list of directories to be searched for header files during preprocessing.
# --enable-option-checking=fatal: make sure invalid --configure-flags are fatal errors instead of just warnings
# --enable-ftp: because ftp_ssl_connect() needs ftp to be compiled statically (see https://github.com/docker-library/php/issues/236)
# --enable-mbstring: because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
# --enable-maintainer-zts: build PHP as ZTS (Zend Thread Safe) to be able to use pthreads
# --with-zlib and --with-zlib-dir: See https://stackoverflow.com/a/42978649/245552
# --enable-opcache-file: allows to use the `opcache.file_cache` option
#
RUN set -xe \
 && ./buildconf --force \
 && CFLAGS="-fstack-protector-strong -fpic -fpie -O3 -I${INSTALL_DIR}/include -I/usr/include -ffunction-sections -fdata-sections" \
    CPPFLAGS="-fstack-protector-strong -fpic -fpie -O3 -I${INSTALL_DIR}/include -I/usr/include -ffunction-sections -fdata-sections" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib -Wl,-O1 -Wl,--strip-all -Wl,--hash-style=both -pie" \
    ./configure \
        --build=x86_64-pc-linux-gnu \
        --prefix=${INSTALL_DIR} \
        --enable-option-checking=fatal \
        --enable-sockets \
        --with-config-file-path=${INSTALL_DIR}/etc/php \
        --with-config-file-scan-dir=${INSTALL_DIR}/etc/php/conf.d:/var/task/php/conf.d \
        --enable-fpm \
        --disable-cgi \
        --enable-cli \
        --disable-phpdbg \
        --disable-phpdbg-webhelper \
        --with-sodium \
        --with-readline \
        --with-openssl \
        --with-zlib=${INSTALL_DIR} \
        --with-zlib-dir=${INSTALL_DIR} \
        --with-curl \
        --enable-exif \
        --enable-ftp \
        --with-gettext \
        --enable-mbstring \
        --with-pdo-mysql=shared,mysqlnd \
        --with-mysqli \
        --enable-pcntl \
        --enable-zip \
        --enable-bcmath \
        --enable-intl=shared \
        --enable-opcache-file \
        --enable-soap \
        --with-xsl=${INSTALL_DIR} \
        --with-gd \
        --with-png-dir=${INSTALL_DIR} \
        --with-jpeg-dir=${INSTALL_DIR}
RUN make -j $(nproc)
# Run `make install` and override PEAR's PHAR URL because pear.php.net is down
RUN set -xe; \
 make install PEAR_INSTALLER_URL='https://github.com/pear/pearweb_phars/raw/master/install-pear-nozlib.phar'; \
 { find ${INSTALL_DIR}/bin ${INSTALL_DIR}/sbin -type f -perm +0111 -exec strip --strip-all '{}' + || true; }; \
 make clean; \
 cp php.ini-production ${INSTALL_DIR}/etc/php/php.ini

# Install extensions
# We can install extensions manually or using `pecl`
RUN pecl install redis
RUN pecl install APCu

# Build extensions
WORKDIR ${IMAGICK_BUILD_DIR}
RUN set -xe; \
    pecl download imagick; \
    tar xzf imagick-${VERSION_IMAGICK_EXTENSION}.tgz
WORKDIR ${IMAGICK_BUILD_DIR}/imagick-${VERSION_IMAGICK_EXTENSION}
RUN set -xe; \
    phpize; \
    ./configure --with-imagick=${INSTALL_DIR}; \
    make -j $(nproc); \
    make install;

# Install Composer
RUN curl -sS https://getcomposer.org/installer | ${INSTALL_DIR}/bin/php -- --install-dir=${INSTALL_DIR}/bin/ --filename=composer

# Symlink all our binaries into /opt/bin so that Lambda sees them in the path.
RUN mkdir -p /opt/bin
RUN ln -s /opt/ymir/bin/* /opt/bin
RUN ln -s /opt/ymir/sbin/* /opt/bin

# Remove extra files to make the layers as slim as possible
COPY clean.sh /tmp
RUN /tmp/clean.sh && rm /tmp/clean.sh

# Copy config files
COPY php.ini ${INSTALL_DIR}/etc/php/conf.d
COPY php-fpm.conf ${INSTALL_DIR}/etc/php-fpm.d

# Now we start back from a clean image.
# We get rid of everything that is unnecessary (build tools, source code, and anything else
# that might have created intermediate layers for docker) by copying online the /opt directory.
FROM amazonlinux:2018.03
ENV PATH="/opt/bin:${PATH}" \
    LD_LIBRARY_PATH="/opt/ymir/lib64:/opt/ymir/lib"

# Copy everything we built above into the same dir on the base AmazonLinux container.
COPY --from=php-build /opt /opt

# Needed for building the layer
COPY --from=php-build /usr/lib64 /usr/lib64

# Needed for creating the build archive
RUN LD_LIBRARY_PATH= yum install -y zip
