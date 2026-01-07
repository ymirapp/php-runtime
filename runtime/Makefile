SHELL := /bin/bash
.PHONY: build publish publish-images publish-dev-images

PHP_VERSIONS := 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5

build: $(addprefix build-php-, $(PHP_VERSIONS))

build-php-%:
	$(eval PHP_VER := $*)
	$(eval PHP_DIR := php-$(shell echo $* | sed 's/\.//g'))
	@echo "Building PHP $(PHP_VER)..."
	rm -f ${PWD}/../build/$(PHP_VER).zip
	rm -f ${PWD}/../build/arm-$(PHP_VER).zip
	# Build x86_64
	depot build -t ymirapp/php-runtime:php-$(PHP_VER) -f $(PHP_DIR)/Dockerfile . --load --build-arg CPU_ARCHITECTURE=x86_64 --build-arg DOCKER_PLATFORM=linux/amd64
	docker run --rm --platform linux/amd64 --entrypoint "/export.sh" --env ARCHIVE_FILENAME=$(PHP_VER) --volume ${PWD}/../:/ymir --volume ${PWD}/export.sh:/export.sh:ro ymirapp/php-runtime:php-$(PHP_VER) .
	# Build arm64
	depot build -t ymirapp/arm-php-runtime:php-$(PHP_VER) -f $(PHP_DIR)/Dockerfile . --load --build-arg CPU_ARCHITECTURE=arm64 --build-arg DOCKER_PLATFORM=linux/arm64
	docker run --rm --platform linux/arm64 --entrypoint "/export.sh" --env ARCHIVE_FILENAME=arm-$(PHP_VER) --volume ${PWD}/../:/ymir --volume ${PWD}/export.sh:/export.sh:ro ymirapp/arm-php-runtime:php-$(PHP_VER) .

publish-images-%:
	$(eval CPU_ARCHITECTURE := $(shell echo '$*' | cut -d '-' -f 2))
	$(eval DOCKER_PLATFORM := $(shell echo '$*' | cut -d '-' -f 1 | sed 's/_/\//'))
	$(eval RUNTIME_REPO := $(if $(filter x86_64,$(CPU_ARCHITECTURE)),ymirapp/php-runtime,ymirapp/arm-php-runtime))
	@for ver in $(PHP_VERSIONS); do \
		PHP_DIR=php-$$(echo $$ver | sed 's/\.//g'); \
		echo "Publishing PHP $$ver for $(CPU_ARCHITECTURE)..."; \
		depot build -t $(RUNTIME_REPO):php-$$ver -f $$PHP_DIR/Dockerfile . --push --platform=$(DOCKER_PLATFORM) --build-arg DOCKER_PLATFORM=$(DOCKER_PLATFORM) --build-arg CPU_ARCHITECTURE=$(CPU_ARCHITECTURE); \
	done

publish-images: publish-images-linux_amd64-x86_64 publish-images-linux_arm64-arm64

publish-dev-images-%:
	$(eval CPU_ARCHITECTURE := $(shell echo '$*' | cut -d '-' -f 2))
	$(eval DOCKER_PLATFORM := $(shell echo '$*' | cut -d '-' -f 1 | sed 's/_/\//'))
	$(eval RUNTIME_REPO := $(if $(filter x86_64,$(CPU_ARCHITECTURE)),ymirapp/php-runtime-dev,ymirapp/arm-php-runtime-dev))
	@for ver in $(PHP_VERSIONS); do \
		PHP_DIR=php-$$(echo $$ver | sed 's/\.//g'); \
		echo "Publishing Dev PHP $$ver for $(CPU_ARCHITECTURE)..."; \
		depot build -t $(RUNTIME_REPO):php-$$ver -f $$PHP_DIR/Dockerfile . --push --platform=$(DOCKER_PLATFORM) --build-arg DOCKER_PLATFORM=$(DOCKER_PLATFORM) --build-arg CPU_ARCHITECTURE=$(CPU_ARCHITECTURE); \
	done

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
