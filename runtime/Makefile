SHELL := /bin/bash
.PHONY: build build-images publish

build: build-php-72.zip build-php-73.zip build-php-74.zip build-php-80.zip build-php-81.zip

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
	cd php-81 ; docker build -t ymir/runtime/php-81 .

publish-images: publish-php-72 publish-php-73 publish-php-74 publish-php-80 publish-php-81

publish-dev-images: publish-dev-php-72 publish-dev-php-73 publish-dev-php-74 publish-dev-php-80 publish-dev-php-81

publish-php%: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3); \
    docker image tag ymir/runtime/$$PHP_VERSION:latest ymirapp/php-runtime:$$PHP_VERSION; \
    docker image push ymirapp/php-runtime:$$PHP_VERSION

publish-dev-php%: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 3,4); \
    docker image tag ymir/runtime/$$PHP_VERSION:latest ymirapp/php-runtime-dev:$$PHP_VERSION; \
    docker image push ymirapp/php-runtime-dev:$$PHP_VERSION

layer-versions:
	./layer-versions

publish:
	./publish
	./layer-versions
	./permissions

layer-versions-prod:
	./layer-versions ymir

publish-prod:
	./publish ymir
	./layer-versions ymir
	./permissions ymir

permissions-prod:
	./permissions ymir
