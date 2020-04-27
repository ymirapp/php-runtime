#!/bin/bash

set -e
set -u
set -x

cd /opt

# Copy over all the runtime layer code
cp -R /ymir/composer.json /ymir/composer.lock /ymir/runtime/bootstrap /ymir/runtime/runtime.php /ymir/src ./

# Set permissions
chmod 0555 /opt/bootstrap /opt/runtime.php

# Install all the non-development dependencies
composer install --no-dev

# Create layer zip
zip --quiet --recurse-paths /ymir/build/${ARCHIVE_FILENAME}.zip .
