SHELL := /bin/bash
.PHONY: build publish

build:
	cd runtime && make build

build-images:
	cd runtime && make build-images

layer-versions:
	cd runtime && make layer-versions

publish:
	cd runtime && make publish

publish-images:
	cd runtime && make publish-images

publish-dev-images:
	cd runtime && make publish-dev-images
