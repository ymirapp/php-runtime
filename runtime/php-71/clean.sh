# ------------------------------------------
# This script cleans extra files from /opt
# to keep the layers as small as possible.
# ------------------------------------------

# Stop on error
set -e
# Treat unset variables and parameters as an error.
set -u

# Strip all the unneeded symbols from shared libraries to reduce size.
find /opt/placeholder -type f -name "*.so*" -o -name "*.a"  -exec strip --strip-unneeded {} \;
find /opt/placeholder -type f -executable -exec sh -c "file -i '{}' | grep -q 'x-executable; charset=binary'" \; -print|xargs strip --strip-all

# Cleanup all the binaries we don't want.
find /opt/placeholder/sbin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+
find /opt/placeholder/bin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+
find /opt/bin -mindepth 1 -maxdepth 1 ! -name "composer" ! -name "php" ! -name "php-fpm" -exec rm {} \+

# Cleanup all the files we don't want either
# We do not support running pear functions in Lambda
rm -rf /opt/placeholder/lib/php/PEAR
rm -rf /opt/placeholder/share/doc
rm -rf /opt/placeholder/share/man
rm -rf /opt/placeholder/share/gtk-doc
rm -rf /opt/placeholder/include
rm -rf /opt/placeholder/lib/php/test
rm -rf /opt/placeholder/lib/php/doc
rm -rf /opt/placeholder/lib/php/docs
rm -rf /opt/placeholder/tests
rm -rf /opt/placeholder/doc
rm -rf /opt/placeholder/docs
rm -rf /opt/placeholder/man
rm -rf /opt/placeholder/www
rm -rf /opt/placeholder/cfg
rm -rf /opt/placeholder/libexec
rm -rf /opt/placeholder/var
rm -rf /opt/placeholder/data
