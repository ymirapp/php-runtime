SHELL := /bin/bash
.PHONY: build publish

build:
	cd runtime && make build

publish:
	cd runtime && make publish
