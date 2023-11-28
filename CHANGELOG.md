# Changelog

## [1.12.0](https://github.com/ymirapp/php-runtime/compare/v1.11.2...v1.12.0) (2023-11-28)


### Features

* add `YMIR_RUNTIME_MAX_INVOCATIONS` env variable to control max invocations ([524f128](https://github.com/ymirapp/php-runtime/commit/524f128ba17d626d5c6f60f1342bc0f66a6a78db))
* add an environment variable for log level ([8e9c105](https://github.com/ymirapp/php-runtime/commit/8e9c1051819358f98dc9465b1799af951252ed7a))
* add bedrock lambda event handler ([b2c6ef6](https://github.com/ymirapp/php-runtime/commit/b2c6ef6f84d3925bec638a7c1325ee0375be9d1e))
* add debug log with fastcgi request details ([e4ae374](https://github.com/ymirapp/php-runtime/commit/e4ae37492158527319941db408116a15661dc322))
* add event handler to warm up additional functions ([4670f85](https://github.com/ymirapp/php-runtime/commit/4670f85d5ed932fb25c8d88607ec59a89e680d44))
* add logging to aws clients ([f697d3e](https://github.com/ymirapp/php-runtime/commit/f697d3e80be3d80dbd44e178b2078a503d10b34c))
* add php 8.1 ([527fe34](https://github.com/ymirapp/php-runtime/commit/527fe34428befbd0da89dc485a0367a95b4b789e))
* add php 8.2 ([5a9fa6a](https://github.com/ymirapp/php-runtime/commit/5a9fa6a1e949b6bccac110257951679d7d23b3af))
* add support for arm64 ([0d8e0f5](https://github.com/ymirapp/php-runtime/commit/0d8e0f5820f0554abcea7b11248772831817bf20))
* add support for multisite ([fbdac73](https://github.com/ymirapp/php-runtime/commit/fbdac73d3923fedd2128f3737c85e2b8165858e4))
* add support for payload version 2.0 ([b9c1d01](https://github.com/ymirapp/php-runtime/commit/b9c1d016aabef8037de4cc1fba8dd9d6414e027f))
* add support for php 8.0 ([e2e2625](https://github.com/ymirapp/php-runtime/commit/e2e2625fb6dbe78b23ad84e40cfb7cc155895ff9))
* add support for webp with imagick ([a03b14a](https://github.com/ymirapp/php-runtime/commit/a03b14a6692df8a740578cdd527fdb44d37f56e5))
* added support for secret environment variables ([6547316](https://github.com/ymirapp/php-runtime/commit/6547316e141c90083145ffc73877af6580fa2bdb))
* bump php versions ([0fbe65d](https://github.com/ymirapp/php-runtime/commit/0fbe65dc80988e4473e23c035b15c18f8f1dbfcc))
* compile phpredis with igbinary and zstd support ([4301b18](https://github.com/ymirapp/php-runtime/commit/4301b18d9a0b511f8ddc94a5b0e9ed12372eaa60))
* drop support for php 7.1 ([cd8129e](https://github.com/ymirapp/php-runtime/commit/cd8129edbc80d9731f5fd71344a2816cdd59ed2b))
* gzip encode html responses ([ae2464b](https://github.com/ymirapp/php-runtime/commit/ae2464b5273ff99b2fc6564a18cbb90ce60ad368))
* publish first set of layers across all regions ([722942d](https://github.com/ymirapp/php-runtime/commit/722942dd855d95de289bda8a3a62845bb4f5c40e))
* remove pthreads from runtime ([1fe561f](https://github.com/ymirapp/php-runtime/commit/1fe561f21e8c5d07e2f963d75e3ef17b7640bb7e))
* rework build process so images can get pushed to docker hub ([b22dfc1](https://github.com/ymirapp/php-runtime/commit/b22dfc1ad627136b47e9ce99f3344690d454c726))
* switch images to amazon linux 2 ([7a10dac](https://github.com/ymirapp/php-runtime/commit/7a10daca172d6b3ca6ddb574fe373151a42006f4))
* switch redis extension for relay ([5dc2b08](https://github.com/ymirapp/php-runtime/commit/5dc2b0886f0a7b6e522965bce0f5306ab3da4cfb))
* updates to php.ini ([82f843c](https://github.com/ymirapp/php-runtime/commit/82f843c37187eef3cff2c2a8a4bc398a1cf7790e))
* upgrade cURL to 8.1.1 and enable TLS 1.3 ([ad1d4f4](https://github.com/ymirapp/php-runtime/commit/ad1d4f4e41099c9115294f71ce8b23d715afc64d))
* upgrade to imagick 7 ([1fa1822](https://github.com/ymirapp/php-runtime/commit/1fa18226048576f6352a138612c3df7557264d70))
* use custom error pages instead of api gateway error messages ([9e24f34](https://github.com/ymirapp/php-runtime/commit/9e24f34592d40020a24581897eb91d85a36635bc))


### Bug Fixes

* `LAMBDA_TASK_ROOT` doesn't point to `/opt` ([5a66460](https://github.com/ymirapp/php-runtime/commit/5a66460e940ecee695a66827c762a505f7380dc9))
* add `lz4` library for relay ([446cdae](https://github.com/ymirapp/php-runtime/commit/446cdaeddd26912a3930973f7db3f5074018761f))
* add missing server variables ([46a2c31](https://github.com/ymirapp/php-runtime/commit/46a2c31996a625ccde3c621fa06b5d791c9d4b58)), closes [#1](https://github.com/ymirapp/php-runtime/issues/1)
* add nginx rewrite rules to bedrock event handler ([06715d4](https://github.com/ymirapp/php-runtime/commit/06715d4414d596900dd635552988952551794fcb))
* always output php-fpm logs by default ([637285c](https://github.com/ymirapp/php-runtime/commit/637285ce2a05030f9bb190d71568196f04aaf570))
* base64 encoded `body` can be larger than original `body` ([eaf85f2](https://github.com/ymirapp/php-runtime/commit/eaf85f2a5f4778c90c2216cb81773a970d37913b))
* bump timeout by 1 second ([9dce5bc](https://github.com/ymirapp/php-runtime/commit/9dce5bc50556866bf0edb4019c8cf0314dfeb7b2))
* change `php` requirement ([294d635](https://github.com/ymirapp/php-runtime/commit/294d63586d9025276f74df720b8daf66da33be04))
* compression response logic should honour `Accept-Encoding` header ([4efc7ab](https://github.com/ymirapp/php-runtime/commit/4efc7abef3b286f855f1fa4313ea3a6ce236409a))
* content length header isn't required for the `trace` http method ([99bb8c0](https://github.com/ymirapp/php-runtime/commit/99bb8c0b73781016ca1f045b35925accb6df449d))
* curl uses `CurlHandle` instead of a resource with php 8 ([8661c32](https://github.com/ymirapp/php-runtime/commit/8661c3202b28b326f23215c4276d95ff18f3fb5a))
* detect additional content types that we can compress ([0b1ebaa](https://github.com/ymirapp/php-runtime/commit/0b1ebaa76e30c3a44e2677e42459ab5d98243c57))
* do not preserve keys when iterating through parameters ([9064873](https://github.com/ymirapp/php-runtime/commit/906487372e8ee67aa6253b0b35225d62e0b36649))
* don't allow `maxInvocations` less than 1 ([58dac08](https://github.com/ymirapp/php-runtime/commit/58dac087415d1cccac04331cc4a1086d8d200766))
* don't exit when an exception gets caught ([ed30237](https://github.com/ymirapp/php-runtime/commit/ed3023780958e026458afd8c580169fc90e985d8))
* don't throw exception when console command fails ([e6dd206](https://github.com/ymirapp/php-runtime/commit/e6dd206c56fee2e64b327baa462e12d473b3ef8f))
* ensure all getters cast their values ([ae03823](https://github.com/ymirapp/php-runtime/commit/ae03823e8c7d8d6300e6cf9dba88bc546315d2f5))
* fix `/wp/wp-login.php` resolving to `/web/index.php` with bedrock ([1e962d5](https://github.com/ymirapp/php-runtime/commit/1e962d5211e64bf08557fe4e17221a6ac9644141))
* fix relay installation ([d855870](https://github.com/ymirapp/php-runtime/commit/d8558703073133399905310a0a976434c010c4df))
* forgot prefix for layer name ([44a793e](https://github.com/ymirapp/php-runtime/commit/44a793e081451172d3b4f9e694929ebec35b86ea))
* forgot to commit some php 8 changes ([45c00c3](https://github.com/ymirapp/php-runtime/commit/45c00c36d965500593bc039e09d18c85b85d0fdd))
* install correct relay version for the right cpu architecture ([1f2f0dd](https://github.com/ymirapp/php-runtime/commit/1f2f0dd669b05c157b115d0e9fd8b53227c5429c))
* move permissions change to its own script ([2048c34](https://github.com/ymirapp/php-runtime/commit/2048c34aa29b8fca508920ac84729444a2489619))
* need to add `/templates` directory to build scripts ([a7595e7](https://github.com/ymirapp/php-runtime/commit/a7595e791507428a5d3a8b888944bb0b6d17524f))
* only compress responses over 6MB ([357fcc1](https://github.com/ymirapp/php-runtime/commit/357fcc1162c67c71ce542ac2a715941c739ef044))
* prioritize `multiValueQueryStringParameters` over `queryStringParameters` ([7e80c29](https://github.com/ymirapp/php-runtime/commit/7e80c291d172d2a28009eea6aa536e89af79b846))
* properly handle `PATH_INFO` ([6be73f0](https://github.com/ymirapp/php-runtime/commit/6be73f05b8e55f0e688bd1ebedd07afaf47961a8))
* remove `X-Powered-By` header ([afa43f4](https://github.com/ymirapp/php-runtime/commit/afa43f40ff41779433060d41060eb75b4369d1b7)), closes [#2](https://github.com/ymirapp/php-runtime/issues/2)
* remove support for `x-forwarded-for` header ([b23993e](https://github.com/ymirapp/php-runtime/commit/b23993eaa6642a4a4071fb57f73bdf69658f4a6e))
* reset streamhandler if php closed it prematurely ([b7bf31f](https://github.com/ymirapp/php-runtime/commit/b7bf31fb939d0b5cf87038600a72f13252652c75))
* return 404 for sensitive files ([5f1dfa5](https://github.com/ymirapp/php-runtime/commit/5f1dfa50c7b6605ddd84a424b57478e412432760))
* return an error page if the response is too large for lambda ([2010f68](https://github.com/ymirapp/php-runtime/commit/2010f68fe7b7dab704e966dc4b491f5989f7f917))
* rework most log entries to be debug entries ([9410917](https://github.com/ymirapp/php-runtime/commit/9410917421c711a80acc7fbf42ccfa051e44cbbf))
* send the correct `REMOTE_ADDR` with the fastcgi request ([85b8dad](https://github.com/ymirapp/php-runtime/commit/85b8dad0d50808efb0f08731265e146a0ab2e7dd))
* should prepend `web` directory for `app` directory as well ([215aa85](https://github.com/ymirapp/php-runtime/commit/215aa8500e4b5282780880ab431d1b82b5c91719))
* switch to a library to detect mime types ([51ea9ec](https://github.com/ymirapp/php-runtime/commit/51ea9ecf580777982655c78f0b32ca72f6a7abce))
* switch to using line formatter ([89e1f77](https://github.com/ymirapp/php-runtime/commit/89e1f7745a5a223d5abb631fe2321c96953decf0))
* update code to change the relay binary id ([6312a57](https://github.com/ymirapp/php-runtime/commit/6312a57abaa153bbf8d2dcffa9d3e5dc042e8a91))
* urlencode query string values ([10bfbe8](https://github.com/ymirapp/php-runtime/commit/10bfbe8518e7c33a031cc4c0433c638ef1aa8f48))
* use `isRunning` to make symfony output php-fpm logs ([477b2a9](https://github.com/ymirapp/php-runtime/commit/477b2a9bb63429ca14e98ce2d9d254f7c25c9918))
* use `rawPath` because AWS trims trailing slash in request context path ([57d62dd](https://github.com/ymirapp/php-runtime/commit/57d62dd89a516347455f6a52f7e6ec2a2b5dee75))
* use new r2 urls ([0d0655c](https://github.com/ymirapp/php-runtime/commit/0d0655cc29e9bfdd0a617a07fa5c7d9931a630ea)), closes [#6](https://github.com/ymirapp/php-runtime/issues/6)
* use php 8.2 version of the relay extension ([857b858](https://github.com/ymirapp/php-runtime/commit/857b8587aa40bc73d0b24a4a7e15af3d999f63bd))
* use symfony 5 `RetryHttpClient` to deal ssm throttle errors ([b26fd6c](https://github.com/ymirapp/php-runtime/commit/b26fd6c573a3459f98161050ce5dfd81111bde92))
* use top version library filename when copying ([dc486a6](https://github.com/ymirapp/php-runtime/commit/dc486a648e958adf93053198438c664b945b0d8d))

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
