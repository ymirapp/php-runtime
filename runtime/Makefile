SHELL := /bin/bash
.PHONY: build build-images publish

build: build-php-72.zip build-php-73.zip

build-php%.zip: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3);\
	rm -f ${PWD}/../build/$@;\
	docker run --rm --entrypoint "/build.sh" --env ARCHIVE_FILENAME=$$PHP_VERSION --volume ${PWD}/../:/runtime --volume ${PWD}/build.sh:/build.sh:ro placeholder/runtime/$$PHP_VERSION .

build-images:
	# Build base Docker image
	cd base ; docker build -t placeholder/runtime/base .
	# Build PHP Docker images
	cd php ; docker build -t placeholder/runtime/php-72 --build-arg PHP_VERSION=7.2.24 .
	cd php ; docker build -t placeholder/runtime/php-73 --build-arg PHP_VERSION=7.3.11 .

publish: publish-php-72.zip publish-php-73.zip

publish-php%.zip: build
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3);\
	./publish $$PHP_VERSION
