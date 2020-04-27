# ------------------------------------------
# This script cleans extra files from /opt
# to keep the layers as small as possible.
# ------------------------------------------

# Stop on error
set -e
# Treat unset variables and parameters as an error.
set -u

# Strip all the unneeded symbols from shared libraries to reduce size.
find /opt/ymir -type f -name "*.so*" -o -name "*.a"  -exec strip --strip-unneeded {} \;
find /opt/ymir -type f -executable -exec sh -c "file -i '{}' | grep -q 'x-executable; charset=binary'" \; -print|xargs strip --strip-all

# Cleanup all the binaries we don't want.
find /opt/ymir/sbin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+
find /opt/ymir/bin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+
find /opt/bin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+

# Cleanup all the files we don't want either
# We do not support running pear functions in Lambda
rm -rf /opt/ymir/lib/php/PEAR
rm -rf /opt/ymir/share/doc
rm -rf /opt/ymir/share/man
rm -rf /opt/ymir/share/gtk-doc
rm -rf /opt/ymir/include
rm -rf /opt/ymir/lib/php/test
rm -rf /opt/ymir/lib/php/doc
rm -rf /opt/ymir/lib/php/docs
rm -rf /opt/ymir/tests
rm -rf /opt/ymir/doc
rm -rf /opt/ymir/docs
rm -rf /opt/ymir/man
rm -rf /opt/ymir/www
rm -rf /opt/ymir/cfg
rm -rf /opt/ymir/libexec
rm -rf /opt/ymir/var
rm -rf /opt/ymir/data
