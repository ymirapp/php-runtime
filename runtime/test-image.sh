#!/bin/bash
set -e

# Ymir Image Verification
# -------------------------------
IMAGE=$1
PLATFORM=$2
if [ -z "$IMAGE" ]; then echo "Usage: $0 <image_name> [platform]"; exit 1; fi
if [ -z "$PLATFORM" ]; then PLATFORM="linux/amd64"; fi

echo "Testing $IMAGE on $PLATFORM..."

# 1. Size (<200MB)
OPT_SIZE_MB=$(( $(docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "du -sk /opt | cut -f1") / 1024 ))
if [ "$OPT_SIZE_MB" -gt 200 ]; then echo "  [FAIL] Size: ${OPT_SIZE_MB}MB"; exit 1; fi
echo "  [OK] Size: ${OPT_SIZE_MB}MB"

# 2. PHP binary
docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -v

# 3. Modules
MODULES=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -m)
PHP_VER=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "echo PHP_VERSION_ID;")
REQUIRED=("apcu" "igbinary" "zstd" "imagick" "intl" "pdo_mysql")
if [ "$PHP_VER" -ge 70400 ]; then
    REQUIRED+=("relay" "msgpack")
else
    REQUIRED+=("redis")
fi
REQUIRED+=("Zend OPcache")
for mod in "${REQUIRED[@]}"; do
    if echo "$MODULES" | grep -qi "$mod"; then echo "  [OK] $mod"; else echo "  [FAIL] $mod missing!"; exit 1; fi
done


# 4. Warnings
WARNINGS=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -v 2>&1 >/dev/null || true)
if [ -n "$WARNINGS" ]; then echo "  [FAIL] Warnings: $WARNINGS"; exit 1; fi
echo "  [OK] No warnings"

# 5. PHP-FPM
FPM_BIN="/opt/ymir/bin/php-fpm"
FPM_CONF="/opt/ymir/etc/php-fpm.d/php-fpm.conf"
docker run --rm --platform "$PLATFORM" --entrypoint $FPM_BIN "$IMAGE" -t --fpm-config $FPM_CONF --allow-to-run-as-root
CID=$(docker run -d --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "mkdir -p /tmp/.ymir && exec $FPM_BIN -F --fpm-config $FPM_CONF --allow-to-run-as-root")
sleep 2
if docker ps -q --no-trunc | grep -q "$CID"; then
    echo "  [OK] PHP-FPM started"; docker stop "$CID" > /dev/null; docker rm "$CID" > /dev/null
else
    echo "  [FAIL] PHP-FPM failed"; docker logs "$CID"; docker rm "$CID" > /dev/null; exit 1
fi
