SHELL := /bin/bash
.PHONY: build build-images publish

build: build-php-72.zip build-php-73.zip build-php-74.zip

build-php%.zip: build-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3);\
	rm -f ${PWD}/../build/$@;\
	docker run --rm --entrypoint "/build.sh" --env ARCHIVE_FILENAME=$$PHP_VERSION --volume ${PWD}/../:/ymir --volume ${PWD}/build.sh:/build.sh:ro ymir/runtime/$$PHP_VERSION .

build-images:
	# Build base Docker image
	cd base ; docker build -t ymir/runtime/base .
	# Build PHP Docker images
	cd php-72 ; docker build -t ymir/runtime/php-72 .
	cd php-73 ; docker build -t ymir/runtime/php-73 .
	cd php-74 ; docker build -t ymir/runtime/php-74 .

layer-versions:
	./layer-versions

publish: build
	./publish
