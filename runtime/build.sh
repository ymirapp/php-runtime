#!/bin/bash

set -e
set -u
set -x

cd /opt

# Copy over all the runtime layer code
cp -R /runtime/composer.json /runtime/composer.lock /runtime/src ./

# Install all the non-development dependencies
composer install --no-dev

# Create layer zip
zip --quiet --recurse-paths /runtime/build/${ARCHIVE_FILENAME}.zip .
