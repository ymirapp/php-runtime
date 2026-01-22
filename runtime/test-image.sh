#!/bin/bash

# Ymir Image Verification
# -------------------------------
IMAGE=$1
PLATFORM=$2
if [ -z "$IMAGE" ]; then echo "Usage: $0 <image_name> [platform]"; exit 1; fi
if [ -z "$PLATFORM" ]; then PLATFORM="linux/amd64"; fi

echo "Testing $IMAGE on $PLATFORM..."

FAILED=0

# 1. Size (<200MB)
OPT_SIZE_MB=$(( $(docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "du -sk /opt | cut -f1") / 1024 ))
if [ "$OPT_SIZE_MB" -gt 200 ]; then
    echo "  [FAIL] Size: ${OPT_SIZE_MB}MB"
    FAILED=1
else
    echo "  [OK] Size: ${OPT_SIZE_MB}MB"
fi

# 2. PHP binary & Version
echo "--------------------------------------------------------------------------------"
docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -v
echo "--------------------------------------------------------------------------------"

# 3. Filesystem layout
FILES_TO_CHECK=("/opt/bootstrap" "/opt/runtime.php" "/opt/vendor" "/opt/vendor/autoload.php")
for file in "${FILES_TO_CHECK[@]}"; do
    if docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "[ -e $file ]"; then
        echo "  [OK] File exists: $file"
    else
        echo "  [FAIL] File missing: $file"
        FAILED=1
    fi
done

# 4. Autoloader
if docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "require '/opt/vendor/autoload.php';" 2>/dev/null; then
    echo "  [OK] Autoloader loadable"
else
    echo "  [FAIL] Autoloader failed to load"
    FAILED=1
fi

# 5. Modules (with standard Lambda LD_LIBRARY_PATH)
LAMBDA_LD_PATH="/opt/lib:/lib64:/usr/lib64"
MODULES=$(docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -m)
PHP_VER=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "echo PHP_VERSION_ID;")

REQUIRED=("apcu" "igbinary" "zstd" "imagick" "intl" "pdo_mysql")
if [ "$PHP_VER" -ge 70400 ]; then
    REQUIRED+=("relay" "msgpack")
else
    REQUIRED+=("redis")
fi
REQUIRED+=("Zend OPcache")

for mod in "${REQUIRED[@]}"; do
    if echo "$MODULES" | grep -qi "$mod"; then
        echo "  [OK] $mod"
    else
        echo "  [FAIL] $mod missing!"
        FAILED=1
    fi
done

# 6. Warnings
WARNINGS=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -v 2>&1 >/dev/null || true)
if [ -n "$WARNINGS" ]; then
    echo "  [FAIL] Warnings: $WARNINGS"
    FAILED=1
else
    echo "  [OK] No warnings"
fi

# 7. PHP-FPM
FPM_BIN="/opt/ymir/bin/php-fpm"
FPM_CONF="/opt/ymir/etc/php-fpm.d/php-fpm.conf"

if docker run --rm --platform "$PLATFORM" --entrypoint $FPM_BIN "$IMAGE" -t --fpm-config $FPM_CONF --allow-to-run-as-root; then
    CID=$(docker run -d --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "mkdir -p /tmp/.ymir && exec $FPM_BIN -F --fpm-config $FPM_CONF --allow-to-run-as-root")
    sleep 2
    if docker ps -q --no-trunc | grep -q "$CID"; then
        echo "  [OK] PHP-FPM started"
        docker stop "$CID" > /dev/null
        docker rm "$CID" > /dev/null
    else
        echo "  [FAIL] PHP-FPM failed to start"
        docker logs "$CID"
        docker rm "$CID" > /dev/null
        FAILED=1
    fi
else
    echo "  [FAIL] PHP-FPM configuration test failed"
    FAILED=1
fi

if [ $FAILED -eq 1 ]; then
    echo "Verification FAILED!"
    exit 1
fi

echo "Verification PASSED!"
exit 0
