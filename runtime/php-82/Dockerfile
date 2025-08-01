# Base Image to use for build.
ARG BASE_IMAGE=base
# CPU Architecture. Can be "x86_64" or "arm64"
ARG CPU_ARCHITECTURE
# Platform used to build the image. Can be "linux/amd64" or "linux/arm64".
ARG DOCKER_PLATFORM

FROM --platform=${DOCKER_PLATFORM} ymirapp/${BASE_IMAGE}:${CPU_ARCHITECTURE} as php-build

###############################################################################
# Oniguruma
# This library is not packaged in PHP since PHP 7.4.
# See https://github.com/php/php-src/blob/43dc7da8e3719d3e89bd8ec15ebb13f997bbbaa9/UPGRADING#L578-L581
# We do not install the system version because I didn't manage to make it work...
# Ideally we shouldn't compile it ourselves.
# https://github.com/kkos/oniguruma/releases
# Needed by:
#   - php mbstring
ENV VERSION_ONIG=6.9.9
ENV ONIG_BUILD_DIR=${BUILD_DIR}/oniguruma
RUN set -xe; \
    mkdir -p ${ONIG_BUILD_DIR}; \
    curl -Ls https://github.com/kkos/oniguruma/releases/download/v${VERSION_ONIG}/onig-${VERSION_ONIG}.tar.gz \
    | tar xzC ${ONIG_BUILD_DIR} --strip-components=1
WORKDIR  ${ONIG_BUILD_DIR}/
RUN set -xe; \
    ./configure --prefix=${INSTALL_DIR}; \
    make -j $(nproc); \
    make install


ENV VERSION_PHP=8.2.28


ENV PHP_BUILD_DIR=${BUILD_DIR}/php
RUN set -xe; \
    mkdir -p ${PHP_BUILD_DIR}; \
    # Download and upack the source code
    # --location will follow redirects
    # --silent will hide the progress, but also the errors: we restore error messages with --show-error
    # --fail makes sure that curl returns an error instead of fetching the 404 page
    curl --location --silent --show-error --fail https://www.php.net/get/php-${VERSION_PHP}.tar.gz/from/this/mirror \
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
# --with-zlib and --with-zlib-dir: See https://stackoverflow.com/a/42978649/245552
# --with-pear: necessary for `pecl` to work (to install PHP extensions)
#
RUN set -xe \
 && ./buildconf --force \
 && CFLAGS="-fstack-protector-strong -fpic -fpie -O3 -I${INSTALL_DIR}/include -I/usr/include -ffunction-sections -fdata-sections" \
    CPPFLAGS="-fstack-protector-strong -fpic -fpie -O3 -I${INSTALL_DIR}/include -I/usr/include -ffunction-sections -fdata-sections" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib -Wl,-O1 -Wl,--strip-all -Wl,--hash-style=both -pie" \
    ./configure \
        --prefix=${INSTALL_DIR} \
        --enable-option-checking=fatal \
        --enable-sockets \
        --with-config-file-path=${INSTALL_DIR}/etc/php \
        --with-config-file-scan-dir=${INSTALL_DIR}/etc/php/conf.d:/var/task/php/conf.d \
        --enable-fpm \
        --disable-cgi \
        --enable-cli \
        --disable-phpdbg \
        --with-sodium \
        --with-readline \
        --with-openssl \
        --with-zlib \
        --with-zlib-dir \
        --with-curl \
        --enable-exif \
        --enable-ftp \
        --with-gettext \
        --enable-mbstring \
        --with-pdo-mysql=shared,mysqlnd \
        --with-mysqli \
        --enable-pcntl \
        --with-zip \
        --enable-bcmath \
        --enable-intl=shared \
        --enable-soap \
        --with-xsl=${INSTALL_DIR} \
        --with-pear
RUN make -j $(nproc)
# Run `make install` and override PEAR's PHAR URL because pear.php.net is down
RUN set -xe; \
 make install PEAR_INSTALLER_URL='https://github.com/pear/pearweb_phars/raw/master/install-pear-nozlib.phar'; \
 { find ${INSTALL_DIR}/bin ${INSTALL_DIR}/sbin -type f -perm +0111 -exec strip --strip-all '{}' + || true; }; \
 make clean; \
 cp php.ini-production ${INSTALL_DIR}/etc/php/php.ini

# Install extensions using pecl
RUN pecl install APCu
RUN pecl install igbinary
RUN pecl install msgpack
RUN pecl install zstd

# Build extensions
WORKDIR ${IMAGICK_BUILD_DIR}
RUN set -xe; \
    pecl download imagick-${VERSION_IMAGICK_EXTENSION}; \
    tar xzf imagick-${VERSION_IMAGICK_EXTENSION}.tgz
WORKDIR ${IMAGICK_BUILD_DIR}/imagick-${VERSION_IMAGICK_EXTENSION}
RUN set -xe; \
    phpize; \
    ./configure --with-imagick=${INSTALL_DIR}; \
    make -j $(nproc); \
    make install;

RUN set -xe; \
    mkdir -p ${RELAY_BUILD_DIR}; \
    RELAY_CPU_ARCHITECTURE=$(arch | sed -e 's/arm64/aarch64/;s/amd64\|x86_64/x86-64/') && \
    curl -L "https://builds.r2.relay.so/v${VERSION_RELAY_EXTENSION}/relay-v${VERSION_RELAY_EXTENSION}-php8.2-centos7-${RELAY_CPU_ARCHITECTURE}.tar.gz" \
    | tar xzC ${RELAY_BUILD_DIR} --strip-components=1
WORKDIR ${RELAY_BUILD_DIR}/
RUN cp relay.ini ${INSTALL_DIR}/etc/php/conf.d/50-relay.ini; \
    cp relay-pkg.so ${INSTALL_DIR}/lib/php/extensions/no-debug-non-zts-20220829/relay.so; \
    sed -i "s/00000000-0000-0000-0000-000000000000/$(cat /proc/sys/kernel/random/uuid)/" ${INSTALL_DIR}/lib/php/extensions/no-debug-non-zts-20220829/relay.so;

# Install Composer
RUN curl -sS https://getcomposer.org/installer | ${INSTALL_DIR}/bin/php -- --install-dir=${INSTALL_DIR}/bin/ --filename=composer

# Symlink all our binaries into /opt/bin so that Lambda sees them in the path.
RUN mkdir -p /opt/bin \
 && cd /opt/bin \
 && ln -s ../ymir/bin/* . \
 && ln -s ../ymir/sbin/* .

# Remove extra files to make the layers as slim as possible
COPY clean.sh /tmp
RUN /tmp/clean.sh && rm /tmp/clean.sh

# Copy config files
COPY php.ini ${INSTALL_DIR}/etc/php/conf.d
COPY php-fpm.conf ${INSTALL_DIR}/etc/php-fpm.d

# Build PHP runtime
RUN git clone https://github.com/ymirapp/php-runtime.git /tmp/runtime-build \
 && cd /tmp/runtime-build \
 && git checkout tags/v1.15.2 \
 && cd /opt \
 && cp -R /tmp/runtime-build/composer.json /tmp/runtime-build/composer.lock /tmp/runtime-build/runtime/bootstrap /tmp/runtime-build/runtime/runtime.php /tmp/runtime-build/src /tmp/runtime-build/templates ./ \
 && chmod 0555 /opt/bootstrap /opt/runtime.php \
 && composer install --no-dev

# Now we start back from a clean image.
# We get rid of everything that is unnecessary (build tools, source code, and anything else
# that might have created intermediate layers for docker) by copying online the /opt directory.
FROM --platform=${DOCKER_PLATFORM} public.ecr.aws/lambda/provided:al2-${CPU_ARCHITECTURE}
ENV PATH="/opt/bin:${PATH}" \
    LD_LIBRARY_PATH="/opt/ymir/lib64:/opt/ymir/lib"

# Copy everything we built above into the same dir on the base AmazonLinux container.
COPY --from=php-build /opt /opt

# Needed for building the layer
COPY --from=php-build /usr/lib64 /usr/lib64
