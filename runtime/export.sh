#!/bin/bash

set -e
set -u
set -x

cd /opt

# Needed for creating the build archive
LD_LIBRARY_PATH= dnf install -y zip

# Create layer zip
zip --quiet --recurse-paths /ymir/build/${ARCHIVE_FILENAME}.zip .
