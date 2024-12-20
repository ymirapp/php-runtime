# Changelog

## [1.12.4](https://github.com/ymirapp/php-runtime/compare/v1.12.3...v1.12.4) (2024-12-20)


### Bug Fixes

* Send output from running console commands to logger ([cfd2460](https://github.com/ymirapp/php-runtime/commit/cfd246014b82be4245436db7f33851e48764eb55))

## [1.12.3](https://github.com/ymirapp/php-runtime/compare/v1.12.2...v1.12.3) (2024-11-23)


### Bug Fixes

* Don't rewrite `/wp-login.php` requests with bedrock ([fb29448](https://github.com/ymirapp/php-runtime/commit/fb29448fb275e5ca6422c340b36cbce4fc6f23c3))

## [1.12.2](https://github.com/ymirapp/php-runtime/compare/v1.12.1...v1.12.2) (2024-06-04)


### Bug Fixes

* event file should always be in the `/web` directory with bedrock ([1620020](https://github.com/ymirapp/php-runtime/commit/16200204b704df165088c24f33042e6a51ae4e9d))

## [1.12.1](https://github.com/ymirapp/php-runtime/compare/v1.12.0...v1.12.1) (2024-03-01)


### Bug Fixes

* fix broken curl build by adding libpsl ([70729cf](https://github.com/ymirapp/php-runtime/commit/70729cf630fe180382628870bc57e15c8820fe80))
* fix libheif build process ([85cb429](https://github.com/ymirapp/php-runtime/commit/85cb429b64dac11ace02fd44d9720ebb8bdfc262))

## [1.12.0](https://github.com/ymirapp/php-runtime/compare/v1.11.2...v1.12.0) (2024-02-13)


### Features

* add php 8.3 ([db3ed1c](https://github.com/ymirapp/php-runtime/commit/db3ed1c21cf5395f8e5a5a9fe9b6f9d4c9d2b514))

## [1.11.2](https://github.com/ymirapp/php-runtime/compare/v1.11.1...v1.11.2) (2023-11-28)


### Bug Fixes

* use php 8.2 version of the relay extension ([857b858](https://github.com/ymirapp/php-runtime/commit/857b8587aa40bc73d0b24a4a7e15af3d999f63bd))

## [1.11.1](https://github.com/ymirapp/php-runtime/compare/v1.11.0...v1.11.1) (2023-11-16)


### Bug Fixes

* base64 encoded `body` can be larger than original `body` ([eaf85f2](https://github.com/ymirapp/php-runtime/commit/eaf85f2a5f4778c90c2216cb81773a970d37913b))

## [1.11.0](https://github.com/ymirapp/php-runtime/compare/v1.10.2...v1.11.0) (2023-11-10)


### Features

* add php 8.2 ([5a9fa6a](https://github.com/ymirapp/php-runtime/commit/5a9fa6a1e949b6bccac110257951679d7d23b3af))


### Bug Fixes

* remove support for `x-forwarded-for` header ([b23993e](https://github.com/ymirapp/php-runtime/commit/b23993eaa6642a4a4071fb57f73bdf69658f4a6e))
