SHELL := /bin/bash
.PHONY: build build-images publish

build: build-php-72.zip build-php-73.zip build-php-74.zip build-php-80.zip build-php-81.zip build-php-82.zip build-php-83.zip

build-php%.zip: publish-images
	PHP_VERSION=$$(echo $@ | cut -d'.' -f 1 | cut -d'-' -f 2,3); \
	rm ${PWD}/../build/$$PHP_VERSION.zip; \
	rm ${PWD}/../build/arm-$$PHP_VERSION.zip; \
	docker run --rm --entrypoint "/export.sh" --env ARCHIVE_FILENAME=$$PHP_VERSION --volume ${PWD}/../:/ymir --volume ${PWD}/export.sh:/export.sh:ro ymirapp/php-runtime:$$PHP_VERSION .; \
	docker run --rm --entrypoint "/export.sh" --env ARCHIVE_FILENAME=arm-$$PHP_VERSION --volume ${PWD}/../:/ymir --volume ${PWD}/export.sh:/export.sh:ro ymirapp/arm-php-runtime:$$PHP_VERSION .

publish-images-%:
	CPU_ARCHITECTURE=$$(echo '$*' | cut -d '-' -f 2); \
	DOCKER_PLATFORM=$$(echo '$*' | cut -d '-' -f 1 | sed 's/_/\//'); \
	if [ "$$CPU_ARCHITECTURE" = "x86_64" ]; then \
		RUNTIME_REPO="ymirapp/php-runtime"; \
	else \
		RUNTIME_REPO="ymirapp/arm-php-runtime"; \
	fi; \
	cd base ; depot build -t ymirapp/base:$$CPU_ARCHITECTURE . --push --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-72 ; depot build -t $$RUNTIME_REPO:php-72 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-73 ; depot build -t $$RUNTIME_REPO:php-73 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-74 ; depot build -t $$RUNTIME_REPO:php-74 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-80 ; depot build -t $$RUNTIME_REPO:php-80 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-81 ; depot build -t $$RUNTIME_REPO:php-81 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
    cd ../php-82 ; depot build -t $$RUNTIME_REPO:php-82 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
    cd ../php-83 ; depot build -t $$RUNTIME_REPO:php-83 . --load --push --build-arg BASE_IMAGE=base --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE

publish-images: publish-images-linux_amd64-x86_64 publish-images-linux_arm64-arm64

publish-dev-images-%:
	CPU_ARCHITECTURE=$$(echo '$*' | cut -d '-' -f 2); \
	DOCKER_PLATFORM=$$(echo '$*' | cut -d '-' -f 1 | sed 's/_/\//'); \
	if [ "$$CPU_ARCHITECTURE" = "x86_64" ]; then \
		RUNTIME_REPO="ymirapp/php-runtime-dev"; \
	else \
		RUNTIME_REPO="ymirapp/arm-php-runtime-dev"; \
	fi; \
	cd base ; depot build -t ymirapp/base-dev:$$CPU_ARCHITECTURE . --push --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-72 ; depot build -t $$RUNTIME_REPO:php-72 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-73 ; depot build -t $$RUNTIME_REPO:php-73 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-74 ; depot build -t $$RUNTIME_REPO:php-74 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-80 ; depot build -t $$RUNTIME_REPO:php-80 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
	cd ../php-81 ; depot build -t $$RUNTIME_REPO:php-81 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
    cd ../php-82 ; depot build -t $$RUNTIME_REPO:php-82 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE; \
    cd ../php-83 ; depot build -t $$RUNTIME_REPO:php-83 . --load --push --build-arg BASE_IMAGE=base-dev --platform=$$DOCKER_PLATFORM --build-arg DOCKER_PLATFORM=$$DOCKER_PLATFORM --build-arg CPU_ARCHITECTURE=$$CPU_ARCHITECTURE

publish-dev-images: publish-dev-images-linux_amd64-x86_64 publish-dev-images-linux_arm64-arm64

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
