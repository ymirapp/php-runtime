# syntax = docker/dockerfile:1.4

# Ymir PHP 7.2
# ------------
# Multi-stage surgical build for AWS Lambda.
# Provides PHP 7.2 + Opcache (shared), APCu, Igbinary, Zstd, Imagick, Redis.
# NOTE: Relay does not support PHP 7.2.

ARG CPU_ARCHITECTURE
ARG DOCKER_PLATFORM

FROM --platform=${DOCKER_PLATFORM} public.ecr.aws/lambda/provided:al2023-${CPU_ARCHITECTURE} as build-environment

ENV BUILD_DIR="/tmp/build" INSTALL_DIR="/opt/ymir"
ENV PKG_CONFIG_PATH="${INSTALL_DIR}/lib64/pkgconfig:${INSTALL_DIR}/lib/pkgconfig:/usr/lib64/pkgconfig:/usr/share/pkgconfig" \
    PKG_CONFIG="/usr/bin/pkg-config" \
    PATH="${INSTALL_DIR}/bin:${PATH}" \
    LD_LIBRARY_PATH="${INSTALL_DIR}/lib64:${INSTALL_DIR}/lib" \
    CMAKE_BUILD_PARALLEL_LEVEL=4 \
    MAKEFLAGS='-j4'

RUN dnf makecache && dnf install -y make gcc gcc-c++ autoconf automake libtool git \
    libcurl-devel zlib-devel readline-devel gettext-devel libicu-devel libxslt-devel libzip-devel oniguruma-devel openssl-devel libsodium-devel libffi-devel libpng-devel libjpeg-devel freetype-devel libwebp-devel libzstd-devel lz4-devel tar xz file \
    perl-FindBin perl-IPC-Cmd

RUN mkdir -p ${BUILD_DIR} ${INSTALL_DIR}/bin ${INSTALL_DIR}/etc/php ${INSTALL_DIR}/etc/php/conf.d ${INSTALL_DIR}/lib ${INSTALL_DIR}/lib64 ${INSTALL_DIR}/sbin

# SQLite 3.51+
ENV VERSION_SQLITE=3.51.1
RUN mkdir -p ${BUILD_DIR}/sqlite && curl -Ls https://github.com/sqlite/sqlite/archive/refs/tags/version-${VERSION_SQLITE}.tar.gz | tar xzC ${BUILD_DIR}/sqlite --strip-components=1
WORKDIR ${BUILD_DIR}/sqlite
RUN CFLAGS="-Os" CPPFLAGS="-Os" ./configure --prefix=${INSTALL_DIR} && make install

# OpenSSL 1.1 (Required for PHP 7.2)
ENV VERSION_OPENSSL11=1.1.1w
RUN mkdir -p ${BUILD_DIR}/openssl11 && curl -Ls https://github.com/openssl/openssl/archive/OpenSSL_${VERSION_OPENSSL11//./_}.tar.gz | tar xzC ${BUILD_DIR}/openssl11 --strip-components=1
WORKDIR ${BUILD_DIR}/openssl11
RUN ./config --prefix=${INSTALL_DIR}/openssl11 --openssldir=${INSTALL_DIR}/openssl11/ssl shared zlib && make -j $(nproc) && make install_sw \
    && cp ${INSTALL_DIR}/openssl11/lib/libssl.so.1.1 ${INSTALL_DIR}/lib/ && cp ${INSTALL_DIR}/openssl11/lib/libcrypto.so.1.1 ${INSTALL_DIR}/lib/

# PHP 7.2.34
ENV VERSION_PHP=7.2.34
RUN mkdir -p ${BUILD_DIR}/php
WORKDIR ${BUILD_DIR}/php
RUN curl --location --silent --show-error --fail https://www.php.net/get/php-${VERSION_PHP}.tar.gz/from/this/mirror | tar xzC . --strip-components=1
RUN ./buildconf --force && \
    export PKG_CONFIG_PATH="${INSTALL_DIR}/openssl11/lib/pkgconfig:$PKG_CONFIG_PATH" && \
    CFLAGS="-fstack-protector-strong -fpic -fpie -Os -I${INSTALL_DIR}/include -I${INSTALL_DIR}/openssl11/include -I/usr/include -ffunction-sections -fdata-sections" \
    CPPFLAGS="-fstack-protector-strong -fpic -fpie -Os -I${INSTALL_DIR}/include -I${INSTALL_DIR}/openssl11/include -I/usr/include -ffunction-sections -fdata-sections" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib -L${INSTALL_DIR}/openssl11/lib -Wl,-O1 -Wl,--strip-all -Wl,--hash-style=both -pie" \
    ./configure \
        --prefix=${INSTALL_DIR} --enable-option-checking=fatal --enable-sockets \
        --with-config-file-path=${INSTALL_DIR}/etc/php --with-config-file-scan-dir=${INSTALL_DIR}/etc/php/conf.d:/var/task/php/conf.d \
        --enable-fpm --disable-cgi --enable-cli --disable-phpdbg --with-sodium --with-readline --with-openssl=${INSTALL_DIR}/openssl11 --with-zlib --with-curl \
        --enable-exif --enable-ftp --with-gettext --enable-mbstring --enable-opcache=shared --with-pdo-mysql=shared,mysqlnd --with-mysqli --enable-pcntl \
        --enable-zip --enable-bcmath --enable-intl=shared --enable-soap --with-xsl --with-pear
RUN make -j $(nproc)
RUN make install PEAR_INSTALLER_URL='https://github.com/pear/pearweb_phars/raw/master/install-pear-nozlib.phar'
RUN cp php.ini-production ${INSTALL_DIR}/etc/php/php.ini

# PECL
RUN set -xe; cd ${BUILD_DIR}; \
    for ext in apcu igbinary zstd; do \
        mkdir -p php-$ext && cd php-$ext && curl -Ls https://pecl.php.net/get/$ext | tar xz && cd $ext-*/ && ${INSTALL_DIR}/bin/phpize && ./configure --with-php-config=${INSTALL_DIR}/bin/php-config && make -j $(nproc) install && cd ${BUILD_DIR}; \
    done

# phpredis (Legacy version 5.3.7 for PHP 7.2 compatibility)
RUN set -xe; mkdir -p ${BUILD_DIR}/php-redis; cd ${BUILD_DIR}/php-redis; \
    curl -Ls https://github.com/phpredis/phpredis/archive/5.3.7.tar.gz | tar xz; cd phpredis-*/; \
    ${INSTALL_DIR}/bin/phpize; ./configure --with-php-config=${INSTALL_DIR}/bin/php-config; make -j $(nproc) install; \
    echo "extension=redis.so" > ${INSTALL_DIR}/etc/php/conf.d/20-redis.ini

# Imagick
ENV VERSION_IMAGICK=7.1.2-12 VERSION_IMAGICK_EXTENSION=3.8.1
RUN mkdir -p ${BUILD_DIR}/imagick && curl -Ls https://github.com/ImageMagick/ImageMagick/archive/${VERSION_IMAGICK}.tar.gz | tar xzC ${BUILD_DIR}/imagick --strip-components=1
WORKDIR ${BUILD_DIR}/imagick
RUN ./configure --prefix ${INSTALL_DIR} --exec-prefix ${INSTALL_DIR} --with-webp --disable-static --with-freetype=yes && make -j $(nproc) install
WORKDIR ${BUILD_DIR}/php-imagick
RUN curl -Ls https://pecl.php.net/get/imagick | tar xz && cd imagick-*/ && ${INSTALL_DIR}/bin/phpize && ./configure --with-imagick=${INSTALL_DIR} --with-php-config=${INSTALL_DIR}/bin/php-config && make -j $(nproc) install

# Composer & Runtime
RUN curl -sS https://getcomposer.org/installer | ${INSTALL_DIR}/bin/php -- --install-dir=${INSTALL_DIR}/bin/ --filename=composer
RUN git clone https://github.com/ymirapp/php-runtime.git /tmp/runtime-build && cd /tmp/runtime-build && git checkout tags/v1.17.3 && cd /opt \
 && cp -R /tmp/runtime-build/composer.json /tmp/runtime-build/composer.lock /tmp/runtime-build/runtime/bootstrap /tmp/runtime-build/runtime/runtime.php /tmp/runtime-build/src /tmp/runtime-build/templates ./ \
 && chmod 0555 /opt/bootstrap /opt/runtime.php && /opt/ymir/bin/php /opt/ymir/bin/composer install --no-dev --optimize-autoloader --no-ansi --no-interaction && find /opt/vendor -type d -name "tests" -exec rm -rf {} + && find /opt/vendor -type d -name "test" -exec rm -rf {} + && find /opt/vendor -type d -name "docs" -exec rm -rf {} + && find /opt/vendor -type f -name "*.md" -delete && find /opt/vendor -type f -name "phpunit.xml*" -delete

# Layer Assembly
RUN mkdir -p /tmp/layer/bin /tmp/layer/ymir/bin /tmp/layer/lib /tmp/layer/ymir/etc/php/conf.d /tmp/layer/ymir/etc/php-fpm.d && rm -rf /tmp/layer/ymir/lib && ln -s ../lib /tmp/layer/ymir/lib
RUN cp ${INSTALL_DIR}/bin/php /tmp/layer/ymir/bin/php && cp ${INSTALL_DIR}/sbin/php-fpm /tmp/layer/ymir/bin/php-fpm && cp ${INSTALL_DIR}/bin/composer /tmp/layer/ymir/bin/composer
RUN ln -s ../ymir/bin/php /tmp/layer/bin/php && ln -s ../ymir/bin/php-fpm /tmp/layer/bin/php-fpm && ln -s ../ymir/bin/composer /tmp/layer/bin/composer
RUN PHP_EXT_DIR=$(${INSTALL_DIR}/bin/php-config --extension-dir) && mkdir -p /tmp/layer/lib/php/extensions && cp -R ${PHP_EXT_DIR} /tmp/layer/lib/php/extensions/
RUN cp -R ${INSTALL_DIR}/etc/php/conf.d/* /tmp/layer/ymir/etc/php/conf.d/ || true
RUN cp ${INSTALL_DIR}/lib/libsqlite3.so* /tmp/layer/lib/ && cp ${INSTALL_DIR}/lib/libssl.so.1.1 /tmp/layer/lib/ || true && cp ${INSTALL_DIR}/lib/libcrypto.so.1.1 /tmp/layer/lib/ || true && \
    cp ${INSTALL_DIR}/lib/libMagickWand-7.Q16HDRI.so* /tmp/layer/lib/ && cp ${INSTALL_DIR}/lib/libMagickCore-7.Q16HDRI.so* /tmp/layer/lib/
COPY shared/copy-dependencies.php /tmp/copy-dependencies.php
RUN ${INSTALL_DIR}/bin/php /tmp/copy-dependencies.php /tmp/layer/ymir/bin /tmp/layer/lib && \
    PHP_EXT_DIR=$(${INSTALL_DIR}/bin/php-config --extension-dir) && ${INSTALL_DIR}/bin/php /tmp/copy-dependencies.php /tmp/layer/lib/php/extensions/$(basename ${PHP_EXT_DIR}) /tmp/layer/lib
RUN find /tmp/layer/ymir/bin -type f -exec strip --strip-all {} + || true && find /tmp/layer/lib -type f -name "*.so*" -exec strip --strip-unneeded {} + || true && find /tmp/layer/lib -type f -name "*.a" -delete && find /tmp/layer/lib -type f -name "*.la" -delete
COPY shared/php.ini /tmp/layer/ymir/etc/php/php.ini
COPY shared/php-fpm.conf /tmp/layer/ymir/etc/php-fpm.d/php-fpm.conf
RUN sed -i '/extension=msgpack.so/d' /tmp/layer/ymir/etc/php/php.ini && \
    sed -i '/decorate_workers_output = no/d' /tmp/layer/ymir/etc/php-fpm.d/php-fpm.conf

FROM --platform=${DOCKER_PLATFORM} public.ecr.aws/lambda/provided:al2023-${CPU_ARCHITECTURE}
ENV PATH="/opt/bin:${PATH}" LD_LIBRARY_PATH="/opt/lib"
COPY --from=build-environment /opt/bootstrap /opt/runtime.php /opt/composer.json /opt/composer.lock /opt/
COPY --from=build-environment /opt/src /opt/src
COPY --from=build-environment /opt/templates /opt/templates
COPY --from=build-environment /opt/vendor /opt/vendor
COPY --from=build-environment /tmp/layer /opt
