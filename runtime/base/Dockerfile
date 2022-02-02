# The container we build here contains everything needed to compile PHP.


# Lambda instances use a custom AMI named Amazon Linux 2, as
# documented under the AWS Lambda Runtimes.
# https://docs.aws.amazon.com/lambda/latest/dg/current-supported-versions.html
# AWS provides it a Docker image that we use here:
# https://github.com/amazonlinux/container-images/tree/amzn2
FROM public.ecr.aws/lambda/provided:al2


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
# - libzip: minimum required CMAKE version 3.0.2
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
ENV VERSION_ZLIB=1.2.11
ENV ZLIB_BUILD_DIR=${BUILD_DIR}/xml2

RUN set -xe; \
    mkdir -p ${ZLIB_BUILD_DIR}; \
# Download and upack the source code
    curl -Ls  http://zlib.net/zlib-${VERSION_ZLIB}.tar.xz \
  | tar xJC ${ZLIB_BUILD_DIR} --strip-components=1

# Move into the unpackaged code directory
WORKDIR  ${ZLIB_BUILD_DIR}/

# Configure the build
RUN set -xe; \
    make distclean \
 && CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
    LDFLAGS="-L${INSTALL_DIR}/lib64 -L${INSTALL_DIR}/lib" \
    ./configure \
    --prefix=${INSTALL_DIR} \
    --64

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
ENV VERSION_OPENSSL=1.1.1m
ENV OPENSSL_BUILD_DIR=${BUILD_DIR}/openssl
ENV CA_BUNDLE_SOURCE="https://curl.se/ca/cacert.pem"
ENV CA_BUNDLE="${INSTALL_DIR}/ssl/cert.pem"


RUN set -xe; \
    mkdir -p ${OPENSSL_BUILD_DIR}; \
# Download and upack the source code
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
ENV VERSION_LIBSSH2=1.10.0
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
ENV VERSION_NGHTTP2=1.46.0
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
# CURL Build
# https://github.com/curl/curl/releases
# Needs:
#   - zlib
#   - OpenSSL
#   - libssh2
# Needed by:
#   - php
ENV VERSION_CURL=7.81.0
ENV CURL_BUILD_DIR=${BUILD_DIR}/curl

RUN set -xe; \
            mkdir -p ${CURL_BUILD_DIR}/bin; \
curl -Ls https://github.com/curl/curl/archive/curl-${VERSION_CURL//./_}.tar.gz \
| tar xzC ${CURL_BUILD_DIR} --strip-components=1


WORKDIR  ${CURL_BUILD_DIR}/

RUN set -xe; \
    ./buildconf \
 && CFLAGS="" \
    CPPFLAGS="-I${INSTALL_DIR}/include  -I/usr/include" \
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
# https://github.com/GNOME/libxml2/releases
# Uses:
#   - zlib
# Needed by:
#   - php
ENV VERSION_XML2=2.9.12
ENV XML2_BUILD_DIR=${BUILD_DIR}/xml2

RUN set -xe; \
    mkdir -p ${XML2_BUILD_DIR}; \
# Download and upack the source code
    curl -Ls http://xmlsoft.org/sources/libxml2-${VERSION_XML2}.tar.gz \
  | tar xzC ${XML2_BUILD_DIR} --strip-components=1

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
ENV VERSION_ZIP=1.8.0
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
ENV VERSION_LIBSODIUM=1.0.18
ENV LIBSODIUM_BUILD_DIR=${BUILD_DIR}/libsodium

RUN set -xe; \
    mkdir -p ${LIBSODIUM_BUILD_DIR}; \
   # Download and unpack the source code
    curl -Ls https://github.com/jedisct1/libsodium/archive/${VERSION_LIBSODIUM}.tar.gz \
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
# libde265 Build
# https://github.com/strukturag/libde265/releases
# Needs:
#
# Needed by:
#   - imagick
ENV LIBDE265_BUILD_DIR=${BUILD_DIR}/libde265
ENV VERSION_LIBDE265=1.0.8

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
ENV VERSION_LIBHEIF=1.12.0

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

RUN LD_LIBRARY_PATH= yum -y install libjpeg-devel libpng-devel libwebp-devel

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
# Redis Build
# https://github.com/phpredis/phpredis/releases
# Needs:
#
# Needed by:
#   - php
ENV REDIS_BUILD_DIR=${BUILD_DIR}/redis
ENV VERSION_REDIS_EXTENSION=5.3.6

# Install some dev files for using old libraries already on the system
# readline-devel : needed for the --with-libedit flag
# gettext-devel : needed for the --with-gettext flag
# libicu-devel : needed for
# libxslt-devel : needed for the XSL extension
# libzstd-devel: needed for PHP Redis
# sqlite-devel : Since PHP 7.4 this must be installed (https://github.com/php/php-src/blob/99b8e67615159fc600a615e1e97f2d1cf18f14cb/UPGRADING#L616-L619)
# re2c : needed for PHP 7.4
RUN LD_LIBRARY_PATH= yum install -y re2c readline-devel gettext-devel libicu-devel libxslt-devel libzstd-devel sqlite-devel

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

# Copy sqlite3 shared libs that are not present in amazonlinux2
RUN cp -a /usr/lib64/libsqlite3*.so* ${INSTALL_DIR}/lib64/

# Copy zstd shared libs that are not present in amazonlinux2
RUN cp /usr/lib64/libzstd.so.1 /opt/ymir/lib/libzstd.so.1

# Copy ImageMagick shared libs that are not present in amazonlinux2
RUN cp /usr/lib64/libpng15.so.15 /opt/ymir/lib/libpng15.so.15
RUN cp /usr/lib64/libjpeg.so.62 /opt/ymir/lib/libjpeg.so.62
RUN cp /usr/lib64/libwebp.so.4 /opt/ymir/lib/libwebp.so.4
RUN cp /usr/lib64/libgomp.so.1 /opt/ymir/lib/libgomp.so.1
