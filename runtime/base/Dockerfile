# The container we build here contains everything needed to compile PHP.

# CPU Architecture. Can be "x86_64" or "arm64"
ARG CPU_ARCHITECTURE
# Platform used to build the image. Can be "linux/amd64" or "linux/arm64".
ARG DOCKER_PLATFORM

# Lambda instances use a custom AMI named Amazon Linux 2, as
# documented under the AWS Lambda Runtimes.
# https://docs.aws.amazon.com/lambda/latest/dg/current-supported-versions.html
# AWS provides it a Docker image that we use here:
# https://github.com/amazonlinux/container-images/tree/amzn2
FROM --platform=${DOCKER_PLATFORM} public.ecr.aws/lambda/provided:al2-${CPU_ARCHITECTURE}


# Move to /tmp to compile everything in there.
WORKDIR /tmp


# Lambda is based on Amazon Linux 2. Lock YUM to that release version.
RUN sed -i 's/releasever=latest/releaserver=amzn2/' /etc/yum.conf


RUN set -xe \
    # Download yum repository data to cache
 && yum makecache \
    # Default Development Tools
 && yum groupinstall -y "Development Tools" --setopt=group_package_types=mandatory,default


# The default version of cmake we can get from the yum repo is 2.8.12. We need cmake to build a few of
# our libraries, and at least one library requires a version of cmake greater than that.
#
# Needed to build:
# - libzip: minimum required CMAKE version 3.0.
RUN LD_LIBRARY_PATH= yum install -y cmake3
RUN ln -s /usr/bin/cmake3 /usr/bin/cmake

# Use the bash shell, instead of /bin/sh
# Why? We need to document this.
SHELL ["/bin/bash", "-c"]

# We need a base path for all the sourcecode we will build from.
ENV BUILD_DIR="/tmp/build"

# We need a base path for the builds to install to. This path must
# match the path that ymir will be unpackaged to in Lambda.
ENV INSTALL_DIR="/opt/ymir"

# Apply stack smash protection to functions using local buffers and alloca()
# ## # Enable size optimization (-Os)
# # Enable linker optimization (this sorts the hash buckets to improve cache locality, and is non-default)
# # Adds GNU HASH segments to generated executables (this is used if present, and is much faster than sysv hash; in this configuration, sysv hash is also generated)

# We need some default compiler variables setup
ENV PKG_CONFIG_PATH="${INSTALL_DIR}/lib64/pkgconfig:${INSTALL_DIR}/lib/pkgconfig" \
    PKG_CONFIG="/usr/bin/pkg-config" \
    PATH="${INSTALL_DIR}/bin:${PATH}"


ENV LD_LIBRARY_PATH="${INSTALL_DIR}/lib64:${INSTALL_DIR}/lib"

# Enable parallelism for cmake (like make -j)
# See https://stackoverflow.com/a/50883540/245552
RUN export CMAKE_BUILD_PARALLEL_LEVEL=$(nproc)

# Ensure we have all the directories we require in the container.
RUN mkdir -p ${BUILD_DIR}  \
    ${INSTALL_DIR}/bin \
    ${INSTALL_DIR}/doc \
    ${INSTALL_DIR}/etc/php \
    ${INSTALL_DIR}/etc/php/conf.d \
    ${INSTALL_DIR}/include \
    ${INSTALL_DIR}/lib \
    ${INSTALL_DIR}/lib64 \
    ${INSTALL_DIR}/libexec \
    ${INSTALL_DIR}/sbin \
    ${INSTALL_DIR}/share

###############################################################################
# ZLIB Build
# https://github.com/madler/zlib/releases
# Needed for:
#   - openssl
#   - curl
#   - php
# Used By:
#   - xml2
ENV VERSION_ZLIB=1.3.1
ENV ZLIB_BUILD_DIR=${BUILD_DIR}/zlib

RUN set -xe; \
    mkdir -p ${ZLIB_BUILD_DIR}; \
    curl -Ls https://github.com/madler/zlib/releases/download/v${VERSION_ZLIB}/zlib-${VERSION_ZLIB}.tar.gz \
  | tar xzC ${ZLIB_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${ZLIB_BUILD_DIR}/

# Configure the build
RUN set -xe; \
    make distclean \
 && CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --prefix=${INSTALL_DIR}

RUN set -xe; \
    make install \
 && rm ${INSTALL_DIR}/lib/libz.a

###############################################################################
# OPENSSL Build
# https://github.com/openssl/openssl/releases
# Needs:
#   - zlib
# Needed by:
#   - curl
#   - php
ENV VERSION_OPENSSL=1.1.1w
ENV OPENSSL_BUILD_DIR=${BUILD_DIR}/openssl
ENV CA_BUNDLE_SOURCE="https://curl.se/ca/cacert.pem"
ENV CA_BUNDLE="${INSTALL_DIR}/ssl/cert.pem"


RUN set -xe; \
    mkdir -p ${OPENSSL_BUILD_DIR}; \
    curl -Ls  https://github.com/openssl/openssl/archive/OpenSSL_${VERSION_OPENSSL//./_}.tar.gz \
  | tar xzC ${OPENSSL_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${OPENSSL_BUILD_DIR}/


# Configure the build
RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./config \
        --prefix=${INSTALL_DIR} \
        --openssldir=${INSTALL_DIR}/ssl \
        --release \
        enable-tls1_3 \
        no-tests \
        shared \
        zlib

RUN set -xe; \
    make install \
 && curl -Lk -o ${CA_BUNDLE} ${CA_BUNDLE_SOURCE}

###############################################################################
# LIBSSH2 Build
# https://github.com/libssh2/libssh2/releases
# Needs:
#   - zlib
#   - OpenSSL
# Needed by:
#   - curl
ENV VERSION_LIBSSH2=1.11.1
ENV LIBSSH2_BUILD_DIR=${BUILD_DIR}/libssh2

RUN set -xe; \
    mkdir -p ${LIBSSH2_BUILD_DIR}/bin; \
    # Download and upack the source code
    curl -Ls https://github.com/libssh2/libssh2/releases/download/libssh2-${VERSION_LIBSSH2}/libssh2-${VERSION_LIBSSH2}.tar.gz \
  | tar xzC ${LIBSSH2_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${LIBSSH2_BUILD_DIR}/bin/

# Configure the build
RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    cmake .. \
    -DBUILD_SHARED_LIBS=ON \
    -DCRYPTO_BACKEND=OpenSSL \
    -DENABLE_ZLIB_COMPRESSION=ON \
    -DCMAKE_INSTALL_PREFIX=${INSTALL_DIR} \
    -DCMAKE_BUILD_TYPE=RELEASE

RUN set -xe; \
    cmake  --build . --target install

###############################################################################
# LIBNGHTTP2 Build
# This adds support for HTTP 2 requests in curl.
# https://github.com/nghttp2/nghttp2/releases
# Needs:
#   - zlib
#   - OpenSSL
# Needed by:
#   - curl
ENV VERSION_NGHTTP2=1.64.0
ENV NGHTTP2_BUILD_DIR=${BUILD_DIR}/nghttp2

RUN set -xe; \
    mkdir -p ${NGHTTP2_BUILD_DIR}; \
    curl -Ls https://github.com/nghttp2/nghttp2/releases/download/v${VERSION_NGHTTP2}/nghttp2-${VERSION_NGHTTP2}.tar.gz \
    | tar xzC ${NGHTTP2_BUILD_DIR} --strip-components=1

WORKDIR  ${NGHTTP2_BUILD_DIR}/

RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --enable-lib-only \
    --prefix=${INSTALL_DIR}

RUN set -xe; \
    make install

###############################################################################
# LIBPSL
# This adds support for the public suffix list in curl.
# https://github.com/rockdaboot/libpsl/releases
# Needed by:
#   - curl
ENV VERSION_LIBPSL=0.21.5
ENV LIBPSL_BUILD_DIR=${BUILD_DIR}/libpsl

RUN set -xe; \
    mkdir -p ${LIBPSL_BUILD_DIR}; \
    curl -Ls https://github.com/rockdaboot/libpsl/releases/download/${VERSION_LIBPSL}/libpsl-${VERSION_LIBPSL}.tar.gz \
    | tar xzC ${LIBPSL_BUILD_DIR} --strip-components=1

WORKDIR  ${LIBPSL_BUILD_DIR}/

RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --prefix=${INSTALL_DIR} \
  && make -j $(nproc) && make install

###############################################################################
# CURL Build
# https://github.com/curl/curl/releases
# Needs:
#   - zlib
#   - OpenSSL
#   - libssh2
# Needed by:
#   - php
ENV VERSION_CURL=8.11.0
ENV CURL_BUILD_DIR=${BUILD_DIR}/curl

RUN set -xe; \
    mkdir -p ${CURL_BUILD_DIR}/bin; \
    curl -Ls https://github.com/curl/curl/archive/curl-${VERSION_CURL//./_}.tar.gz \
    | tar xzC ${CURL_BUILD_DIR} --strip-components=1

WORKDIR  ${CURL_BUILD_DIR}/

RUN set -xe; \
    ./buildconf \
 && CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --prefix=${INSTALL_DIR} \
    --with-ca-bundle=${CA_BUNDLE} \
    --enable-shared \
    --disable-static \
    --enable-optimize \
    --disable-warnings \
    --disable-dependency-tracking \
    --with-zlib \
    --enable-http \
    --enable-ftp  \
    --enable-file \
    --enable-proxy  \
    --enable-tftp \
    --enable-ipv6 \
    --enable-openssl-auto-load-config \
    --enable-cookies \
    --with-gnu-ld \
    --with-ssl \
    --with-libssh2 \
    --with-nghttp2


RUN set -xe; \
    make install

###############################################################################
# LIBXML2 Build
# https://github.com/GNOME/libxml2/tags
# Uses:
#   - zlib
# Needed by:
#   - php
ENV VERSION_XML2=2.13.5
ENV XML2_BUILD_DIR=${BUILD_DIR}/xml2

RUN set -xe; \
    mkdir -p ${XML2_BUILD_DIR}; \
# Download and upack the source code
    curl -Ls https://download.gnome.org/sources/libxml2/${VERSION_XML2%.*}/libxml2-${VERSION_XML2}.tar.xz \
  | tar xJC ${XML2_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${XML2_BUILD_DIR}/

# Configure the build
RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --prefix=${INSTALL_DIR} \
    --with-sysroot=${INSTALL_DIR} \
    --enable-shared \
    --disable-static \
    --with-html \
    --with-history \
    --enable-ipv6=no \
    --with-icu \
    --with-zlib=${INSTALL_DIR} \
    --without-python

RUN set -xe; \
    make install \
 && cp xml2-config ${INSTALL_DIR}/bin/xml2-config

###############################################################################
# LIBZIP Build
# https://github.com/nih-at/libzip/releases
# Needed by:
#   - php
ENV VERSION_ZIP=1.11.2
ENV ZIP_BUILD_DIR=${BUILD_DIR}/zip

RUN set -xe; \
    mkdir -p ${ZIP_BUILD_DIR}/bin/; \
# Download and upack the source code
    curl -Ls https://github.com/nih-at/libzip/releases/download/v${VERSION_ZIP}/libzip-${VERSION_ZIP}.tar.gz \
  | tar xzC ${ZIP_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${ZIP_BUILD_DIR}/bin/

# Configure the build
RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    cmake .. \
    -DCMAKE_INSTALL_PREFIX=${INSTALL_DIR} \
    -DCMAKE_BUILD_TYPE=RELEASE

RUN set -xe; \
    cmake  --build . --target install

###############################################################################
# LIBSODIUM Build
# https://github.com/jedisct1/libsodium/releases
# Needs:
#
# Needed by:
#   - php
ENV VERSION_LIBSODIUM=1.0.20
ENV LIBSODIUM_BUILD_DIR=${BUILD_DIR}/libsodium

RUN set -xe; \
    mkdir -p ${LIBSODIUM_BUILD_DIR}; \
    curl -Ls https://github.com/jedisct1/libsodium/archive/${VERSION_LIBSODIUM}-RELEASE.tar.gz \
  | tar xzC ${LIBSODIUM_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${LIBSODIUM_BUILD_DIR}/

# Configure the build
RUN set -xe; \
    CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./autogen.sh \
&& ./configure --prefix=${INSTALL_DIR}

RUN set -xe; \
    make install

###############################################################################
# SQLite Build
# https://github.com/sqlite/sqlite/tags
# Needs:
#
# Needed by:
#   - php
ENV VERSION_SQLITE=3.48.0
ENV SQLITE_BUILD_DIR=${BUILD_DIR}/sqlite

RUN set -xe; \
    mkdir -p ${SQLITE_BUILD_DIR}; \
    curl -Ls https://github.com/sqlite/sqlite/archive/refs/tags/version-${VERSION_SQLITE}.tar.gz \
  | tar xzC ${SQLITE_BUILD_DIR} --strip-components=1

WORKDIR ${SQLITE_BUILD_DIR}

RUN set -xe; \
    ./configure --prefix=${INSTALL_DIR}; \
    make; \
    make install

###############################################################################
# libwebp Build
# https://github.com/webmproject/libwebp/tags
# Needs:
#
# Needed by:
#   - imagick
ENV LIBWEBP_BUILD_DIR=${BUILD_DIR}/libwebp
ENV VERSION_LIBWEBP=1.5.0

RUN set -xe; \
    mkdir -p ${LIBWEBP_BUILD_DIR}; \
    curl -Ls https://github.com/webmproject/libwebp/archive/refs/tags/v${VERSION_LIBWEBP}.tar.gz \
    | tar xzC ${LIBWEBP_BUILD_DIR} --strip-components=1

WORKDIR ${LIBWEBP_BUILD_DIR}

RUN set -xe; \
    autoreconf -i; \
    automake; \
    autoconf; \
    ./configure --prefix ${INSTALL_DIR} --exec-prefix ${INSTALL_DIR}; \
    make -j $(nproc); \
    make install

###############################################################################
# libde265 Build
# https://github.com/strukturag/libde265/releases
# Needs:
#
# Needed by:
#   - imagick
ENV LIBDE265_BUILD_DIR=${BUILD_DIR}/libde265
ENV VERSION_LIBDE265=1.0.15

RUN set -xe; \
    mkdir -p ${LIBDE265_BUILD_DIR}; \
    curl -Ls https://github.com/strukturag/libde265/releases/download/v${VERSION_LIBDE265}/libde265-${VERSION_LIBDE265}.tar.gz \
    | tar xzC ${LIBDE265_BUILD_DIR} --strip-components=1

WORKDIR ${LIBDE265_BUILD_DIR}

RUN set -xe; \
    ./configure --prefix ${INSTALL_DIR} --exec-prefix ${INSTALL_DIR}; \
    make -j $(nproc); \
    make install

###############################################################################
# libheif Build
# https://github.com/strukturag/libheif/releases
# Needs:
#
# Needed by:
#   - imagick
ENV LIBHEIF_BUILD_DIR=${BUILD_DIR}/libheif
ENV VERSION_LIBHEIF=1.13.0

RUN set -xe; \
    mkdir -p ${LIBHEIF_BUILD_DIR}; \
    curl -Ls https://github.com/strukturag/libheif/releases/download/v${VERSION_LIBHEIF}/libheif-${VERSION_LIBHEIF}.tar.gz \
    | tar xzC ${LIBHEIF_BUILD_DIR} --strip-components=1

WORKDIR ${LIBHEIF_BUILD_DIR}

RUN set -xe; \
    ./configure --prefix ${INSTALL_DIR} --exec-prefix ${INSTALL_DIR}; \
    make -j $(nproc); \
    make install


###############################################################################
# Imagick Build
# https://github.com/ImageMagick/ImageMagick6/releases
# Needs:
#
# Needed by:
#   - php
ENV IMAGICK_BUILD_DIR=${BUILD_DIR}/imagick
ENV VERSION_IMAGICK=7.1.0-22
ENV VERSION_IMAGICK_EXTENSION=3.7.0

RUN LD_LIBRARY_PATH= yum -y install libjpeg-devel libpng-devel

RUN set -xe; \
    mkdir -p ${IMAGICK_BUILD_DIR}; \
    curl -Ls https://github.com/ImageMagick/ImageMagick/archive/${VERSION_IMAGICK}.tar.gz \
    | tar xzC ${IMAGICK_BUILD_DIR} --strip-components=1

WORKDIR ${IMAGICK_BUILD_DIR}

RUN set -xe; \
    ./configure --prefix ${INSTALL_DIR} --exec-prefix ${INSTALL_DIR} --with-webp --with-heic --disable-static --with-freetype=yes; \
    make -j $(nproc); \
    make install

###############################################################################
# Relay Build
# https://github.com/cachewerk/relay/releases
# Needs:
#
# Needed by:
#   - php
ENV RELAY_BUILD_DIR=${BUILD_DIR}/relay
ENV VERSION_RELAY_EXTENSION=0.9.0

# Install some dev files for using old libraries already on the system
# readline-devel : needed for the --with-libedit flag
# gettext-devel : needed for the --with-gettext flag
# libicu-devel : needed for intl
# libxslt-devel : needed for the XSL extension
# libzstd-devel: needed for Relay
# lz4-devel : needed for Relay
# re2c : needed for PHP 7.4
RUN LD_LIBRARY_PATH= yum install -y re2c readline-devel gettext-devel libicu-devel libxslt-devel libzstd-devel lz4-devel

RUN cp -a /usr/lib64/libgcrypt.so* ${INSTALL_DIR}/lib64/

# Copy readline shared libs that are not present in amazonlinux2
RUN cp -a /usr/lib64/libreadline.so?* ${INSTALL_DIR}/lib64/

# Copy gpg-error shared libds that are not present in amazonlinux2
RUN cp -a /usr/lib64/libgpg-error.so* ${INSTALL_DIR}/lib64/

# Copy gettext shared libs that are not present in amazonlinux2
RUN cp -a /usr/lib64/libasprintf.so* ${INSTALL_DIR}/lib64/
RUN cp -a /usr/lib64/libgettextpo.so* ${INSTALL_DIR}/lib64/
RUN cp -a /usr/lib64/preloadable_libintl.so* ${INSTALL_DIR}/lib64/

# Copy xslt shared libs that are not present in amazonlinux2
RUN cp -a /usr/lib64/lib*xslt*.so* ${INSTALL_DIR}/lib64/

# Copy zstd shared libs that are not present in amazonlinux2
RUN cp /usr/lib64/libzstd.so.1 /opt/ymir/lib/libzstd.so.1

# Copy lz4 shared libs that are not present in amazonlinux2
RUN cp /usr/lib64/liblz4.so.1 /opt/ymir/lib/liblz4.so.1

# Copy ImageMagick shared libs that are not present in amazonlinux2
RUN cp /usr/lib64/libpng15.so.15 /opt/ymir/lib/libpng15.so.15
RUN cp /usr/lib64/libjpeg.so.62 /opt/ymir/lib/libjpeg.so.62
RUN cp /usr/lib64/libgomp.so.1 /opt/ymir/lib/libgomp.so.1
