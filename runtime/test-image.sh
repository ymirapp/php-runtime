#!/bin/bash

# Ymir Image Verification
# -------------------------------
IMAGE=$1
PLATFORM=$2
if [ -z "$IMAGE" ]; then echo "Usage: $0 <image_name> [platform]"; exit 1; fi
if [ -z "$PLATFORM" ]; then PLATFORM="linux/amd64"; fi

echo "Testing $IMAGE on $PLATFORM..."

FAILED=0
trap 'exit 130' INT
trap 'exit 143' TERM

# Size
OPT_SIZE_KB=$(docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "du -sk /opt | cut -f1") || exit 1
if ! [[ "$OPT_SIZE_KB" =~ ^[0-9]+$ ]]; then echo "  [FAIL] Could not determine image size"; exit 1; fi
OPT_SIZE_MB=$(( OPT_SIZE_KB / 1024 ))

if [ "$OPT_SIZE_MB" -gt 250 ]; then
    echo "  [FAIL] Size: ${OPT_SIZE_MB}MB (exceeds 250MB Lambda limit)"
    FAILED=1
elif [ "$OPT_SIZE_MB" -gt 150 ]; then
    echo "  [FAIL] Size: ${OPT_SIZE_MB}MB (zip too large)"
    FAILED=1
else
    echo "  [OK] Size: ${OPT_SIZE_MB}MB"
fi

# PHP binary & Version
echo "--------------------------------------------------------------------------------"
docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -v || FAILED=1
echo "--------------------------------------------------------------------------------"

# Filesystem layout
FILES_TO_CHECK=("/opt/bootstrap" "/opt/runtime.php" "/opt/vendor" "/opt/vendor/autoload.php" "/opt/src" "/opt/templates")
for file in "${FILES_TO_CHECK[@]}"; do
    if docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "[ -e $file ]"; then
        echo "  [OK] File exists: $file"
    else
        echo "  [FAIL] File missing: $file"
        FAILED=1
    fi
done

# Modules (with standard Lambda LD_LIBRARY_PATH)
LAMBDA_LD_PATH="/opt/lib:/lib64:/usr/lib64"
# Capture both stdout and stderr to detect startup warnings
MODULE_OUTPUT=$(docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -m 2>&1)
MODULES=$(echo "$MODULE_OUTPUT" | grep -v "PHP Warning" | grep -v "Failed loading" || true)
STARTUP_ERRORS=$(echo "$MODULE_OUTPUT" | grep -E "PHP Warning|Failed loading|Unable to load" || true)

if [ -n "$STARTUP_ERRORS" ]; then
    echo "  [FAIL] PHP Startup Errors detected:"
    echo "--------------------------------------------------------------------------------"
    echo "$STARTUP_ERRORS"
    echo "--------------------------------------------------------------------------------"
    FAILED=1
fi

PHP_VER=$(docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "echo PHP_VERSION_ID;" 2>/dev/null) || exit 1
if ! [[ "$PHP_VER" =~ ^[0-9]+$ ]]; then echo "  [FAIL] Could not determine PHP version"; exit 1; fi

REQUIRED=("apcu" "igbinary" "zstd" "imagick" "intl" "pdo_mysql" "pdo_pgsql" "pgsql")
if [ -n "$PHP_VER" ] && [ "$PHP_VER" -ge 70400 ]; then
    REQUIRED+=("relay" "msgpack")
else
    REQUIRED+=("redis")
fi
REQUIRED+=("Zend OPcache")

for mod in "${REQUIRED[@]}"; do
    if echo "$MODULES" | grep -qi "$mod"; then
        echo "  [OK] Module loaded: $mod"
    else
        echo "  [FAIL] Module missing: $mod"
        FAILED=1
    fi
done

# Extension File Verification
echo "  [INFO] Verifying extension paths..."
EXT_DIR=$(docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "echo ini_get('extension_dir');" 2>/dev/null)
if [ -z "$EXT_DIR" ]; then
    echo "  [FAIL] Could not determine extension_dir"
    FAILED=1
else
    if ! [[ "$EXT_DIR" =~ ^/opt/ymir/lib/php/extensions/no-debug-non-zts-[0-9]{8}$ ]]; then
        echo "  [FAIL] extension_dir changed: $EXT_DIR"
        FAILED=1
    fi
    if docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "[ -d $EXT_DIR ]"; then
        echo "  [OK] Extension directory exists: $EXT_DIR"
        INI_CONTENT=$(docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "cat /opt/ymir/etc/php/php.ini /opt/ymir/etc/php/conf.d/*.ini 2>/dev/null") || exit 1
        # shortcut: shipped INIs use relative .so names; normalize paths if that format changes.
        INI_EXTENSIONS=$(printf '%s\n' "$INI_CONTENT" | sed -nE 's/^[[:space:]]*(zend_)?extension[[:space:]]*=[[:space:]]*"?([^";[:space:]]+)"?.*/\2/p')
        if [ -z "$INI_EXTENSIONS" ]; then echo "  [FAIL] No extension entries found in shipped INI files"; exit 1; fi
        for ext in "pdo_mysql.so" "pdo_pgsql.so" "pgsql.so" $INI_EXTENSIONS; do
            if docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "[ -f $EXT_DIR/$ext ]"; then
                echo "  [OK] Extension file exists: $ext"
            else
                echo "  [FAIL] Extension file missing: $EXT_DIR/$ext"
                FAILED=1
            fi
        done
        # Opcache can be built-in or shared
        if ! docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -m | grep -qi "Zend OPcache"; then
             if ! docker run --rm --platform "$PLATFORM" --entrypoint /bin/sh "$IMAGE" -c "[ -f $EXT_DIR/opcache.so ]"; then
                 echo "  [FAIL] Zend OPcache missing (not in php -m and no opcache.so found)"
                 FAILED=1
             fi
        fi
    else
        echo "  [FAIL] Extension directory missing: $EXT_DIR"
        FAILED=1
    fi
fi

# Relay Configuration
if [ -n "$PHP_VER" ] && [ "$PHP_VER" -ge 70400 ]; then
    EVICTION_POLICY=$(docker run --rm --platform "$PLATFORM" -e LD_LIBRARY_PATH="$LAMBDA_LD_PATH" --entrypoint /opt/ymir/bin/php "$IMAGE" -r "echo ini_get('relay.eviction_policy');" 2>/dev/null)
    if [ "$EVICTION_POLICY" = "lru" ]; then
        echo "  [OK] relay.eviction_policy is lru"
    else
        echo "  [FAIL] relay.eviction_policy is '$EVICTION_POLICY' (expected lru)"
        FAILED=1
    fi
fi

# Warnings (Comprehensive check)
# Check multiple commands to ensure no hidden startup warnings
PHP_COMMANDS=("-v" "-i" "-m")
ANY_WARNINGS=""
for cmd in "${PHP_COMMANDS[@]}"; do
    CMD_WARNINGS=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" $cmd 2>&1 >/dev/null | grep -E "Warning|Error|failed" || true)
    if [ -n "$CMD_WARNINGS" ]; then
        ANY_WARNINGS="${ANY_WARNINGS}\nCommand 'php $cmd':\n$CMD_WARNINGS"
    fi
done

if [ -n "$ANY_WARNINGS" ]; then
    echo "  [FAIL] PHP Startup Warnings/Errors detected:"
    echo -e "$ANY_WARNINGS"
    FAILED=1
else
    echo "  [OK] No PHP startup warnings"
fi

# Generic TLS Crypto Validation
TLS_OUTPUT=$(docker run --rm --platform "$PLATFORM" --entrypoint /opt/ymir/bin/php "$IMAGE" -r '$content=file_get_contents("https://www.google.com");if(false===$content){echo "tls_fetch_failed\n";exit(2);}echo "tls_fetch_ok\n";' 2>&1)
TLS_STATUS=$?

if [ "$TLS_STATUS" -eq 0 ] && echo "$TLS_OUTPUT" | grep -q "tls_fetch_ok"; then
    echo "  [OK] Outbound TLS crypto succeeded"
else
    echo "  [FAIL] Outbound TLS crypto failed"
    echo "--------------------------------------------------------------------------------"
    echo "$TLS_OUTPUT"
    echo "--------------------------------------------------------------------------------"
    FAILED=1
fi

# Lambda invocations through RIE (also covers runtime boot, PHP-FPM startup and default paths)
# Run in the background so INT/TERM reach the suite, which removes its containers before exiting.
python3 "$(dirname "$0")/../tests/Integration/test_runtime.py" "$IMAGE" "$PLATFORM" &
SUITE_PID=$!
trap 'kill -INT "$SUITE_PID"; wait "$SUITE_PID"; exit 130' INT
trap 'kill -TERM "$SUITE_PID"; wait "$SUITE_PID"; exit 143' TERM
wait "$SUITE_PID" || FAILED=1

if [ $FAILED -eq 1 ]; then
    echo "Verification FAILED!"
    exit 1
fi

echo "Verification PASSED!"
exit 0
