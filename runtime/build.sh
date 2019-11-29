#!/bin/bash

set -e
set -u
set -x

cd /opt

# Copy over all the runtime layer code
cp -R /placeholder/composer.json /placeholder/composer.lock /placeholder/runtime/bootstrap /placeholder/runtime/runtime.php /placeholder/src ./

# Set permissions
chmod 0555 /opt/bootstrap /opt/runtime.php

# Install all the non-development dependencies
composer install --no-dev

# Create layer zip
zip --quiet --recurse-paths /placeholder/build/${ARCHIVE_FILENAME}.zip .
