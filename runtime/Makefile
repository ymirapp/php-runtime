SHELL := /bin/bash
.PHONY: build build-images publish

build: build-php-72.zip build-php-73.zip build-php-74.zip build-php-80.zip

build-php%.zip: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3); \
	rm -f ${PWD}/../build/$@; \
	docker run --rm --entrypoint "/export.sh" --env ARCHIVE_FILENAME=$$PHP_VERSION --volume ${PWD}/../:/ymir --volume ${PWD}/export.sh:/export.sh:ro ymir/runtime/$$PHP_VERSION .

build-images:
	# Build base Docker image
	cd base ; docker build -t ymir/runtime/base .
	# Build PHP Docker images
	cd php-72 ; docker build -t ymir/runtime/php-72 .
	cd php-73 ; docker build -t ymir/runtime/php-73 .
	cd php-74 ; docker build -t ymir/runtime/php-74 .
	cd php-80 ; docker build -t ymir/runtime/php-80 .

publish-images: publish-php-72 publish-php-73 publish-php-74

publish-php%: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3); \
    docker image tag ymir/runtime/$$PHP_VERSION:latest ymirapp/php-runtime:$$PHP_VERSION; \
    docker image push ymirapp/php-runtime:$$PHP_VERSION

layer-versions:
	./layer-versions

publish: build
	./publish
