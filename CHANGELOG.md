# Changelog

## [1.20.0](https://github.com/ymirapp/php-runtime/compare/v1.19.3...v1.20.0) (2026-06-30)


### Features

* Add `PhpScriptApplication` as a `_HANDLER`-based fallback ([5a7b69d](https://github.com/ymirapp/php-runtime/commit/5a7b69d513f9052904d1cc175ef858df35387900))
* Add `YMIR_RUNTIME_MAX_INVOCATIONS` env variable to control max invocations ([524f128](https://github.com/ymirapp/php-runtime/commit/524f128ba17d626d5c6f60f1342bc0f66a6a78db))
* Add acorn sqs handler ([5fae426](https://github.com/ymirapp/php-runtime/commit/5fae42623c0cc442642dfad4b1b81494ebbdee07))
* Add an environment variable for log level ([8e9c105](https://github.com/ymirapp/php-runtime/commit/8e9c1051819358f98dc9465b1799af951252ed7a))
* Add bedrock lambda event handler ([b2c6ef6](https://github.com/ymirapp/php-runtime/commit/b2c6ef6f84d3925bec638a7c1325ee0375be9d1e))
* Add debug log with fastcgi request details ([e4ae374](https://github.com/ymirapp/php-runtime/commit/e4ae37492158527319941db408116a15661dc322))
* Add event handler for laravel ([67d6040](https://github.com/ymirapp/php-runtime/commit/67d60404742462a47935a34e67a846504e545f16))
* Add event handler for radicle ([c0bde25](https://github.com/ymirapp/php-runtime/commit/c0bde25e613593d9e692af4fd74a2bd54fb922d3))
* Add event handler to warm up additional functions ([4670f85](https://github.com/ymirapp/php-runtime/commit/4670f85d5ed932fb25c8d88607ec59a89e680d44))
* Add laravel handler for sqs events ([51ae7a6](https://github.com/ymirapp/php-runtime/commit/51ae7a61c8809841895baf15643062852f194e90))
* Add logging to aws clients ([f697d3e](https://github.com/ymirapp/php-runtime/commit/f697d3e80be3d80dbd44e178b2078a503d10b34c))
* Add php 8.1 ([527fe34](https://github.com/ymirapp/php-runtime/commit/527fe34428befbd0da89dc485a0367a95b4b789e))
* Add php 8.2 ([5a9fa6a](https://github.com/ymirapp/php-runtime/commit/5a9fa6a1e949b6bccac110257951679d7d23b3af))
* Add php 8.3 ([db3ed1c](https://github.com/ymirapp/php-runtime/commit/db3ed1c21cf5395f8e5a5a9fe9b6f9d4c9d2b514))
* Add php 8.4 ([ff14738](https://github.com/ymirapp/php-runtime/commit/ff1473811243a5241763832c7c0cdb072f3690a1))
* Add php 8.5 ([28eb125](https://github.com/ymirapp/php-runtime/commit/28eb1252f8e8761f4f2d78e4c02ee9374a480267))
* Add postgres extension ([7b57af1](https://github.com/ymirapp/php-runtime/commit/7b57af1410555a50dea06ea3e017f8466c2bfd78))
* Add queue function runtime ([74c440e](https://github.com/ymirapp/php-runtime/commit/74c440e8483601b22e844ff5e86a509ff7cbb734))
* Add request and trace id to http requests and responses ([3cd567d](https://github.com/ymirapp/php-runtime/commit/3cd567d110b463752c5fc8f13d85845e7c8795bb))
* Add support for arm64 ([0d8e0f5](https://github.com/ymirapp/php-runtime/commit/0d8e0f5820f0554abcea7b11248772831817bf20))
* Add support for multisite ([fbdac73](https://github.com/ymirapp/php-runtime/commit/fbdac73d3923fedd2128f3737c85e2b8165858e4))
* Add support for payload version 2.0 ([b9c1d01](https://github.com/ymirapp/php-runtime/commit/b9c1d016aabef8037de4cc1fba8dd9d6414e027f))
* Add support for php 8.0 ([e2e2625](https://github.com/ymirapp/php-runtime/commit/e2e2625fb6dbe78b23ad84e40cfb7cc155895ff9))
* Add support for webp with imagick ([a03b14a](https://github.com/ymirapp/php-runtime/commit/a03b14a6692df8a740578cdd527fdb44d37f56e5))
* Added support for secret environment variables ([6547316](https://github.com/ymirapp/php-runtime/commit/6547316e141c90083145ffc73877af6580fa2bdb))
* Bump php versions ([0fbe65d](https://github.com/ymirapp/php-runtime/commit/0fbe65dc80988e4473e23c035b15c18f8f1dbfcc))
* Compile phpredis with igbinary and zstd support ([4301b18](https://github.com/ymirapp/php-runtime/commit/4301b18d9a0b511f8ddc94a5b0e9ed12372eaa60))
* Create a dynamic timeout for php-fpm based on remaining execution time ([6cd929f](https://github.com/ymirapp/php-runtime/commit/6cd929febbee99be01e5015f54679d04554d4ccc))
* Disable jit and switch to storing opcode in a file cache ([5c774cc](https://github.com/ymirapp/php-runtime/commit/5c774ccb841ad03485878936aba87f0b619be108))
* Don't display deprecation notices when the runtime is starting ([abd781b](https://github.com/ymirapp/php-runtime/commit/abd781beacb6e720312fe4c9103d85c3dcce93db))
* Drop support for php 7.1 ([cd8129e](https://github.com/ymirapp/php-runtime/commit/cd8129edbc80d9731f5fd71344a2816cdd59ed2b))
* Gzip encode html responses ([ae2464b](https://github.com/ymirapp/php-runtime/commit/ae2464b5273ff99b2fc6564a18cbb90ce60ad368))
* Publish first set of layers across all regions ([722942d](https://github.com/ymirapp/php-runtime/commit/722942dd855d95de289bda8a3a62845bb4f5c40e))
* Remove pthreads from runtime ([1fe561f](https://github.com/ymirapp/php-runtime/commit/1fe561f21e8c5d07e2f963d75e3ef17b7640bb7e))
* Rework build process so images can get pushed to docker hub ([b22dfc1](https://github.com/ymirapp/php-runtime/commit/b22dfc1ad627136b47e9ce99f3344690d454c726))
* Set command timeout based on remaining time ([8c594ff](https://github.com/ymirapp/php-runtime/commit/8c594fff3aa8576e5b4ec23f76bd7b1d3f5f06fb))
* Switch images to amazon linux 2 ([7a10dac](https://github.com/ymirapp/php-runtime/commit/7a10daca172d6b3ca6ddb574fe373151a42006f4))
* Switch redis extension for relay ([5dc2b08](https://github.com/ymirapp/php-runtime/commit/5dc2b0886f0a7b6e522965bce0f5306ab3da4cfb))
* Switch to `lru` for relay `eviction_policy` ([838bf8f](https://github.com/ymirapp/php-runtime/commit/838bf8f9a98671a403520c3dfd9e55aa29ff9de7))
* Switch to compiling libwebp ([4014aa9](https://github.com/ymirapp/php-runtime/commit/4014aa921c9fe4f4ce39e89e2c8c2d22dd988f52))
* Switch to compiling sqlite ([ea275ae](https://github.com/ymirapp/php-runtime/commit/ea275ae37fa8917f2a3fc70e401a032e5d178795))
* Switch to compiling zlib ([e2ebd02](https://github.com/ymirapp/php-runtime/commit/e2ebd02757c1a43fbd3291b1429f64cfcc6786e7))
* Switch to using `CloudWatchFormatter` from ymir monolog library ([6f1eeb3](https://github.com/ymirapp/php-runtime/commit/6f1eeb358237a324c6544adaa74c0d3b0a18c79f))
* Turn on jit opcache for all php 8 releases ([d66982f](https://github.com/ymirapp/php-runtime/commit/d66982f517c549403c5d3fa0d4a484c9b51105fb))
* Updates to php.ini ([82f843c](https://github.com/ymirapp/php-runtime/commit/82f843c37187eef3cff2c2a8a4bc398a1cf7790e))
* Upgrade cURL to 8.1.1 and enable TLS 1.3 ([ad1d4f4](https://github.com/ymirapp/php-runtime/commit/ad1d4f4e41099c9115294f71ce8b23d715afc64d))
* Upgrade to al2023 ([756cba0](https://github.com/ymirapp/php-runtime/commit/756cba06b50121cd05c1a0ee4bc03c4e4877062d))
* Upgrade to imagick 7 ([1fa1822](https://github.com/ymirapp/php-runtime/commit/1fa18226048576f6352a138612c3df7557264d70))
* Use custom error pages instead of api gateway error messages ([9e24f34](https://github.com/ymirapp/php-runtime/commit/9e24f34592d40020a24581897eb91d85a36635bc))


### Bug Fixes

* `jit_buffer_size` cannot go over 128M on aarch64 ([6ae62bb](https://github.com/ymirapp/php-runtime/commit/6ae62bbcd47d3ca6ce593e501483dda9bb276463))
* `LAMBDA_TASK_ROOT` doesn't point to `/opt` ([5a66460](https://github.com/ymirapp/php-runtime/commit/5a66460e940ecee695a66827c762a505f7380dc9))
* Add `lz4` library for relay ([446cdae](https://github.com/ymirapp/php-runtime/commit/446cdaeddd26912a3930973f7db3f5074018761f))
* Add missing server variables ([46a2c31](https://github.com/ymirapp/php-runtime/commit/46a2c31996a625ccde3c621fa06b5d791c9d4b58)), closes [#1](https://github.com/ymirapp/php-runtime/issues/1)
* Add nginx rewrite rules to bedrock event handler ([06715d4](https://github.com/ymirapp/php-runtime/commit/06715d4414d596900dd635552988952551794fcb))
* Add php-fpm `log_limit` to prevent long stderr messages from being truncated ([c28ee5f](https://github.com/ymirapp/php-runtime/commit/c28ee5ff6231f9d9136ef9ff60e1d66fa3086c64))
* Always output php-fpm logs by default ([637285c](https://github.com/ymirapp/php-runtime/commit/637285ce2a05030f9bb190d71568196f04aaf570))
* Base64 encoded `body` can be larger than original `body` ([eaf85f2](https://github.com/ymirapp/php-runtime/commit/eaf85f2a5f4778c90c2216cb81773a970d37913b))
* Bump timeout by 1 second ([9dce5bc](https://github.com/ymirapp/php-runtime/commit/9dce5bc50556866bf0edb4019c8cf0314dfeb7b2))
* Change `php` requirement ([294d635](https://github.com/ymirapp/php-runtime/commit/294d63586d9025276f74df720b8daf66da33be04))
* Compression response logic should honour `Accept-Encoding` header ([4efc7ab](https://github.com/ymirapp/php-runtime/commit/4efc7abef3b286f855f1fa4313ea3a6ce236409a))
* Content length header isn't required for the `trace` http method ([99bb8c0](https://github.com/ymirapp/php-runtime/commit/99bb8c0b73781016ca1f045b35925accb6df449d))
* Curl uses `CurlHandle` instead of a resource with php 8 ([8661c32](https://github.com/ymirapp/php-runtime/commit/8661c3202b28b326f23215c4276d95ff18f3fb5a))
* Detect additional content types that we can compress ([0b1ebaa](https://github.com/ymirapp/php-runtime/commit/0b1ebaa76e30c3a44e2677e42459ab5d98243c57))
* Do not preserve keys when iterating through parameters ([9064873](https://github.com/ymirapp/php-runtime/commit/906487372e8ee67aa6253b0b35225d62e0b36649))
* Don't allow `maxInvocations` less than 1 ([58dac08](https://github.com/ymirapp/php-runtime/commit/58dac087415d1cccac04331cc4a1086d8d200766))
* Don't exit when an exception gets caught ([ed30237](https://github.com/ymirapp/php-runtime/commit/ed3023780958e026458afd8c580169fc90e985d8))
* Don't rewrite `/wp-login.php` requests with bedrock ([fb29448](https://github.com/ymirapp/php-runtime/commit/fb29448fb275e5ca6422c340b36cbce4fc6f23c3))
* Don't throw exception when console command fails ([e6dd206](https://github.com/ymirapp/php-runtime/commit/e6dd206c56fee2e64b327baa462e12d473b3ef8f))
* Downgrade libheif to fix build issue ([8448abc](https://github.com/ymirapp/php-runtime/commit/8448abc9a0594ef42d1c06adc1281608677fc774))
* Downgrade libxml2 version to fix pear install on older php versions ([796b13c](https://github.com/ymirapp/php-runtime/commit/796b13c95e4e25caf0c8fa9e0b009efdd71ec699))
* Ensure all getters cast their values ([ae03823](https://github.com/ymirapp/php-runtime/commit/ae03823e8c7d8d6300e6cf9dba88bc546315d2f5))
* Event file should always be in the `/web` directory with bedrock ([1620020](https://github.com/ymirapp/php-runtime/commit/16200204b704df165088c24f33042e6a51ae4e9d))
* Fix `/wp/wp-login.php` resolving to `/web/index.php` with bedrock ([1e962d5](https://github.com/ymirapp/php-runtime/commit/1e962d5211e64bf08557fe4e17221a6ac9644141))
* Fix broken curl build by adding libpsl ([70729cf](https://github.com/ymirapp/php-runtime/commit/70729cf630fe180382628870bc57e15c8820fe80))
* Fix broken libsodium build ([1cd905f](https://github.com/ymirapp/php-runtime/commit/1cd905f60bbef1bced5de87feff3505e25262ec5))
* Fix libheif build process ([85cb429](https://github.com/ymirapp/php-runtime/commit/85cb429b64dac11ace02fd44d9720ebb8bdfc262))
* Fix missing transient dependencies and autoloader paths in zip layers ([20088de](https://github.com/ymirapp/php-runtime/commit/20088de7524a059ea4e578b97829562f98e2ffec))
* Fix relay installation ([d855870](https://github.com/ymirapp/php-runtime/commit/d8558703073133399905310a0a976434c010c4df))
* Flush streamed sqs process output ([56fa193](https://github.com/ymirapp/php-runtime/commit/56fa193d8faa23baa96ad6f3805d62f1969a3c6c))
* Force `APP_RUNNING_IN_CONSOLE=true` when initializing laravel-based applications ([3d9f490](https://github.com/ymirapp/php-runtime/commit/3d9f490c67ba8b6f05ee0249a181acb2458521ef))
* Forgot prefix for layer name ([44a793e](https://github.com/ymirapp/php-runtime/commit/44a793e081451172d3b4f9e694929ebec35b86ea))
* Forgot to commit some php 8 changes ([45c00c3](https://github.com/ymirapp/php-runtime/commit/45c00c36d965500593bc039e09d18c85b85d0fdd))
* Install correct relay version for the right cpu architecture ([1f2f0dd](https://github.com/ymirapp/php-runtime/commit/1f2f0dd669b05c157b115d0e9fd8b53227c5429c))
* Kill the lambda container if we get a `ReadFailedException` exception ([1c95fcb](https://github.com/ymirapp/php-runtime/commit/1c95fcb1e07b6d40a6fcaab10c984cc0346c17f3))
* Make `memory_limit` match the maximum allowed memory for lambdas ([b18fc2f](https://github.com/ymirapp/php-runtime/commit/b18fc2fb21be1c8a72f62029fcd2cd982e5bf56f))
* Move permissions change to its own script ([2048c34](https://github.com/ymirapp/php-runtime/commit/2048c34aa29b8fca508920ac84729444a2489619))
* Need to add `/templates` directory to build scripts ([a7595e7](https://github.com/ymirapp/php-runtime/commit/a7595e791507428a5d3a8b888944bb0b6d17524f))
* Only compress responses over 6MB ([357fcc1](https://github.com/ymirapp/php-runtime/commit/357fcc1162c67c71ce542ac2a715941c739ef044))
* Preserve `src` and `templates` directory structure in zip layers ([0ce0e58](https://github.com/ymirapp/php-runtime/commit/0ce0e583c2036e85abff8ce1668c3b8e2eb95050))
* Prioritize `multiValueQueryStringParameters` over `queryStringParameters` ([7e80c29](https://github.com/ymirapp/php-runtime/commit/7e80c291d172d2a28009eea6aa536e89af79b846))
* Properly handle `PATH_INFO` ([6be73f0](https://github.com/ymirapp/php-runtime/commit/6be73f05b8e55f0e688bd1ebedd07afaf47961a8))
* Reduce zip layer size ([2953929](https://github.com/ymirapp/php-runtime/commit/2953929ccf4a91b2c4431443bae0ec28a1d94e83))
* Remove `--no-ansi` from radicle acorn cache initialization command ([a76ab05](https://github.com/ymirapp/php-runtime/commit/a76ab052af166748e721b6e7e321c7b4e4cfad83))
* Remove `X-Powered-By` header ([afa43f4](https://github.com/ymirapp/php-runtime/commit/afa43f40ff41779433060d41060eb75b4369d1b7)), closes [#2](https://github.com/ymirapp/php-runtime/issues/2)
* Remove support for `x-forwarded-for` header ([b23993e](https://github.com/ymirapp/php-runtime/commit/b23993eaa6642a4a4071fb57f73bdf69658f4a6e))
* Reset streamhandler if php closed it prematurely ([b7bf31f](https://github.com/ymirapp/php-runtime/commit/b7bf31fb939d0b5cf87038600a72f13252652c75))
* Restart php-fpm and retry once after fastcgi connect failure ([5cb8808](https://github.com/ymirapp/php-runtime/commit/5cb880870451cb63ba0c34c03a6168470070f28d))
* Return 404 for sensitive files ([5f1dfa5](https://github.com/ymirapp/php-runtime/commit/5f1dfa50c7b6605ddd84a424b57478e412432760))
* Return an error page if the response is too large for lambda ([2010f68](https://github.com/ymirapp/php-runtime/commit/2010f68fe7b7dab704e966dc4b491f5989f7f917))
* Revert `jit` option to default `tracing` mode ([39d0fff](https://github.com/ymirapp/php-runtime/commit/39d0fffe21389165eb01387ed07036f10c228639))
* Rework most log entries to be debug entries ([9410917](https://github.com/ymirapp/php-runtime/commit/9410917421c711a80acc7fbf42ccfa051e44cbbf))
* Send output from running console commands to logger ([cfd2460](https://github.com/ymirapp/php-runtime/commit/cfd246014b82be4245436db7f33851e48764eb55))
* Send the correct `REMOTE_ADDR` with the fastcgi request ([85b8dad](https://github.com/ymirapp/php-runtime/commit/85b8dad0d50808efb0f08731265e146a0ab2e7dd))
* Set openssl ca trust store for php 7.2-8.0 images ([ba6fdc1](https://github.com/ymirapp/php-runtime/commit/ba6fdc1a76aa860e364b01c6bbbb19142916aaaf))
* Should prepend `web` directory for `app` directory as well ([215aa85](https://github.com/ymirapp/php-runtime/commit/215aa8500e4b5282780880ab431d1b82b5c91719))
* Stream sqs queue process output ([866185d](https://github.com/ymirapp/php-runtime/commit/866185dc9f50f5bdf2e10183d688889e58fd8e23))
* Strip leading `php` from php console payloads ([c3302d6](https://github.com/ymirapp/php-runtime/commit/c3302d6a75215dbf57f7c5fe9d90e95f1e45156f))
* Switch to a library to detect mime types ([51ea9ec](https://github.com/ymirapp/php-runtime/commit/51ea9ecf580777982655c78f0b32ca72f6a7abce))
* Switch to using line formatter ([89e1f77](https://github.com/ymirapp/php-runtime/commit/89e1f7745a5a223d5abb631fe2321c96953decf0))
* Throw exception if we're unable to connect to php-fpm ([32e7843](https://github.com/ymirapp/php-runtime/commit/32e78432a20bb67aa1cd227546f62bad0f031023))
* Update code to change the relay binary id ([6312a57](https://github.com/ymirapp/php-runtime/commit/6312a57abaa153bbf8d2dcffa9d3e5dc042e8a91))
* Urlencode query string values ([10bfbe8](https://github.com/ymirapp/php-runtime/commit/10bfbe8518e7c33a031cc4c0433c638ef1aa8f48))
* Use `isRunning` to make symfony output php-fpm logs ([477b2a9](https://github.com/ymirapp/php-runtime/commit/477b2a9bb63429ca14e98ce2d9d254f7c25c9918))
* Use `rawPath` because AWS trims trailing slash in request context path ([57d62dd](https://github.com/ymirapp/php-runtime/commit/57d62dd89a516347455f6a52f7e6ec2a2b5dee75))
* Use new r2 urls ([0d0655c](https://github.com/ymirapp/php-runtime/commit/0d0655cc29e9bfdd0a617a07fa5c7d9931a630ea)), closes [#6](https://github.com/ymirapp/php-runtime/issues/6)
* Use php 8.2 version of the relay extension ([857b858](https://github.com/ymirapp/php-runtime/commit/857b8587aa40bc73d0b24a4a7e15af3d999f63bd))
* Use symfony 5 `RetryHttpClient` to deal ssm throttle errors ([b26fd6c](https://github.com/ymirapp/php-runtime/commit/b26fd6c573a3459f98161050ce5dfd81111bde92))
* Use top version library filename when copying ([dc486a6](https://github.com/ymirapp/php-runtime/commit/dc486a648e958adf93053198438c664b945b0d8d))
* Write php-fpm errors to stderr ([e24bd86](https://github.com/ymirapp/php-runtime/commit/e24bd862bedcfc4b3d24e90fba52ae65f5b59cf1))


### Dependency Changes

* Disable platform check ([2cf0354](https://github.com/ymirapp/php-runtime/commit/2cf035480c8a4f7ec7d36527123474be79aefce8))
* Ran `composer update` ([b6840b6](https://github.com/ymirapp/php-runtime/commit/b6840b6f41fd3c3fd12fd18d546e620f8defbe25))
* Ran `composer update` ([f960de3](https://github.com/ymirapp/php-runtime/commit/f960de3c8e7126fda59274bbf55f7d90bcad7ef7))
* Ran `composer update` ([116124a](https://github.com/ymirapp/php-runtime/commit/116124a73a8833ec72141e08d003802f7842d8d4))
* Ran `composer update` ([7fa830f](https://github.com/ymirapp/php-runtime/commit/7fa830f8034ce1086a598d6b0ec4ff958ecc7941))
* Ran `composer update` ([2e3b386](https://github.com/ymirapp/php-runtime/commit/2e3b386da20d4fcbbc66bf2ce5959bc099681c56))
* Ran `composer update` ([4897419](https://github.com/ymirapp/php-runtime/commit/4897419b2fe28f7a1054e83f276ed94cde6fe686))
* Ran `composer update` ([8826cc1](https://github.com/ymirapp/php-runtime/commit/8826cc1e680b8ef4260515a53091ae4f4994dc7d))
* Ran `composer update` ([3bb0f72](https://github.com/ymirapp/php-runtime/commit/3bb0f725879f7b5f8fd48c14ca2d1813d1f1f36b))
* Ran `composer update` ([ed5d693](https://github.com/ymirapp/php-runtime/commit/ed5d69322c11894895237c621bfed412bb5ac827))
* Ran `composer update` ([442c051](https://github.com/ymirapp/php-runtime/commit/442c051265df548c32abd4cd9a3f435d734b5610))
* Ran `composer update` ([0123c38](https://github.com/ymirapp/php-runtime/commit/0123c38acae5b21d966aae09d44c3165a757368f))
* Ran `composer update` ([6680250](https://github.com/ymirapp/php-runtime/commit/6680250a22dc1b934965c4412a49f42c7521e43a))
* Ran `composer update` ([fa8ae75](https://github.com/ymirapp/php-runtime/commit/fa8ae75b630384a44d0855d742ab5d8f80905949))
* Ran `composer update` ([68798b3](https://github.com/ymirapp/php-runtime/commit/68798b3636903f2f36cd4bcaa087e36a8fd5faba))
* Ran `composer update` ([d3b2bcb](https://github.com/ymirapp/php-runtime/commit/d3b2bcbf0265eb27710f28bb58e9a19f568652b8))
* Ran `composer update` ([8a4aa07](https://github.com/ymirapp/php-runtime/commit/8a4aa079586c5877d9e31c6189714b16cbad8ca2))
* Updated composer dependencies ([1cda050](https://github.com/ymirapp/php-runtime/commit/1cda05093531df54064b94ae96d03ed7eb0af2fa))
* Updated composer dependencies ([374f1cf](https://github.com/ymirapp/php-runtime/commit/374f1cf04866502d297db5712aa917eeba41fb6f))
* Updated composer dependencies ([7fcaab7](https://github.com/ymirapp/php-runtime/commit/7fcaab793fcff9b61bd63df93e5e7d9dab707b9c))
* Updated composer dependencies ([2ceca69](https://github.com/ymirapp/php-runtime/commit/2ceca698ff5d3bc47596f21c6ae60f8f5d849c33))
* Updated composer dependencies ([ab7968c](https://github.com/ymirapp/php-runtime/commit/ab7968c78179ca2c18945c53ab59f32fc903a201))
* Upgrade curl to 8.14.1 ([17a6a2c](https://github.com/ymirapp/php-runtime/commit/17a6a2cf3cfc1d1c3c98122b752e6bb5920e5758))
* Upgrade curl to 8.15.0 ([21a750c](https://github.com/ymirapp/php-runtime/commit/21a750c691909a85b782cead845b0a7748b0be41))
* Upgrade curl to 8.17.0 ([8a7781a](https://github.com/ymirapp/php-runtime/commit/8a7781ab1451cbeb31f6e3b10b89be935ac18ea5))
* Upgrade imagick extension to 3.8.0 ([5091e1f](https://github.com/ymirapp/php-runtime/commit/5091e1f41b0cd3b262b0f958f8ceccd41e6c5e48))
* Upgrade imagick extension to 3.8.1 ([a9a710b](https://github.com/ymirapp/php-runtime/commit/a9a710b53a26ba6b248ebd79f8a76defb070da0c))
* Upgrade imagick to 7.1.1-47 ([6e92b87](https://github.com/ymirapp/php-runtime/commit/6e92b8728c2c5b3c0e8efc067dcf34687ca19722))
* Upgrade imagick to 7.1.2-1 ([f88bd21](https://github.com/ymirapp/php-runtime/commit/f88bd21fcf5ab7e57781afb202575a53381726f2))
* Upgrade imagick to 7.1.2-12 ([83500ed](https://github.com/ymirapp/php-runtime/commit/83500ed1c0bc24a88ab7d5b79ee12a360c1334c8))
* Upgrade imagick to 7.1.2-22 ([afce65a](https://github.com/ymirapp/php-runtime/commit/afce65ab2e9c3c194553122cb72e86e173661f1f))
* Upgrade imagick to 7.1.2-26 ([31fd499](https://github.com/ymirapp/php-runtime/commit/31fd499e6026524ee0fc4eeec0ffcfc638d405b9))
* Upgrade imagick to 7.1.2-8 ([efe1a49](https://github.com/ymirapp/php-runtime/commit/efe1a498c1a6ae9a05f4d4629297d0641ef207e8))
* Upgrade libde265 to 1.0.16 ([8f76dca](https://github.com/ymirapp/php-runtime/commit/8f76dca04b93b973aa0d89ea199f6fb66247ebce))
* Upgrade libwebp to 1.6.0 ([0acc651](https://github.com/ymirapp/php-runtime/commit/0acc6510cfce23a0a0c9a13895e887036a04a571))
* Upgrade libzip to 1.11.4 ([0c0ae5d](https://github.com/ymirapp/php-runtime/commit/0c0ae5d763e3eb6266843cc4176f1fd490fc13a7))
* Upgrade nghttp2 to 1.66.0 ([fe8b590](https://github.com/ymirapp/php-runtime/commit/fe8b590dca8bc39107e382dd19ed9a52e22d428b))
* Upgrade nghttp2 to 1.66.0 ([d4328b6](https://github.com/ymirapp/php-runtime/commit/d4328b69760b0b1fff88df20d503c1596407d2f6))
* Upgrade php versions ([e94cbaa](https://github.com/ymirapp/php-runtime/commit/e94cbaa7e4b17c68c57d15aeb7a0aa976431d065))
* Upgrade php versions ([688b610](https://github.com/ymirapp/php-runtime/commit/688b610b311d754e0624310a7c50e9983222a894))
* Upgrade relay to 0.11.0 ([239fad2](https://github.com/ymirapp/php-runtime/commit/239fad2a72e7c986a4fa0ef48f9cca585e530b58))
* Upgrade relay to 0.11.1 ([ffca105](https://github.com/ymirapp/php-runtime/commit/ffca105f00c45e4babd228d47081ca9651da20f0))
* Upgrade relay to 0.12.1 ([8e61f4d](https://github.com/ymirapp/php-runtime/commit/8e61f4d3d73c0673b29bc235c3efdc67e113737d))
* Upgrade relay to 0.20.0 ([758a083](https://github.com/ymirapp/php-runtime/commit/758a0834b8217cd97eaaa45c5d82ecd58e456aa5)), closes [#35](https://github.com/ymirapp/php-runtime/issues/35)
* Upgrade relay to 0.22.0 ([8e1ddae](https://github.com/ymirapp/php-runtime/commit/8e1ddaeea55cecc40f79428294f9a11afcd8dfa5))
* Upgrade relay to 0.30.0 ([d722012](https://github.com/ymirapp/php-runtime/commit/d722012a51199836698c429fae7cb45dc5ebd178))
* Upgrade sqlite to 3.50.4 ([1aa0a61](https://github.com/ymirapp/php-runtime/commit/1aa0a618ed4537a3ea5a98a94f67f3c501457f04))
* Upgrade sqlite to 3.51.0 ([99c93e0](https://github.com/ymirapp/php-runtime/commit/99c93e050324c7ae4089baac42c9c4933f93fc32))
* Upgrade sqlite to 3.51.1 ([10c244c](https://github.com/ymirapp/php-runtime/commit/10c244c48c3ac160aec03ca6518666006bd968ca))
* Upgrade sqlite to 3.51.2 ([c976911](https://github.com/ymirapp/php-runtime/commit/c976911e2b6d656e51394aa55a2b943246ea9a4c))
* Upgrade sqlite to 3.53.1 ([bf76b7c](https://github.com/ymirapp/php-runtime/commit/bf76b7cd298cd42a1672a4a7b1998e978b48b303))
* Upgrade sqlite to 3.53.3 ([5dd2e5b](https://github.com/ymirapp/php-runtime/commit/5dd2e5b1144a179de526f3a603e1a2bc65bc14a4))
* Upgrade to grumphp v1 ([5dea1a7](https://github.com/ymirapp/php-runtime/commit/5dea1a74bb87d00e261743a6f46cc597c49af46a))
* Upgrade to monolog 2.0 ([59f7150](https://github.com/ymirapp/php-runtime/commit/59f7150bcd5e0866c4b21860b9bbb9558abe27d6))
* Upgrade to php 8.1.34 ([9e6343c](https://github.com/ymirapp/php-runtime/commit/9e6343c01af3014e6e1d1f9685c27c6024f7e131))
* Upgrade to php 8.2.30 ([40802b0](https://github.com/ymirapp/php-runtime/commit/40802b026636905290e3401be2c35c688a79f0a8))
* Upgrade to php 8.2.31 ([c5bf53c](https://github.com/ymirapp/php-runtime/commit/c5bf53c9c4c8e5e0bbb29a79dd13cee0fd214aa2))
* Upgrade to php 8.3.29 ([6dcac6e](https://github.com/ymirapp/php-runtime/commit/6dcac6ec67e932011c0186976189ceb539aad11d))
* Upgrade to php 8.3.30 ([3d89271](https://github.com/ymirapp/php-runtime/commit/3d892714e8350590ea47c49a7194ab5cc3e367ff))
* Upgrade to php 8.3.31 ([3955008](https://github.com/ymirapp/php-runtime/commit/39550082e2d8a422b70db8f63a445c9171eb2ac2))
* Upgrade to php 8.4.16 ([a944801](https://github.com/ymirapp/php-runtime/commit/a9448017fa7bbd7aa97b749605fdeb0b086bb9a4))
* Upgrade to php 8.4.18 ([997e4ab](https://github.com/ymirapp/php-runtime/commit/997e4ab7dda2cee0f85139da0204ec7d02764684))
* Upgrade to php 8.4.21 ([a5c130f](https://github.com/ymirapp/php-runtime/commit/a5c130f20683c8fad99f2410b7d2922e20520261))
* Upgrade to php 8.4.22 ([be01c69](https://github.com/ymirapp/php-runtime/commit/be01c691f6ba5c0ac9e619363887d9aa071040ae))
* Upgrade to php 8.5.3 ([a7d9143](https://github.com/ymirapp/php-runtime/commit/a7d91435ce5623f5afbeda27878e26aaac9a65f8))
* Upgrade to php 8.5.6 ([89bfee7](https://github.com/ymirapp/php-runtime/commit/89bfee7246eeb66ec9ee602b15843547f77b3fad))
* Upgrade to php 8.5.7 ([d315688](https://github.com/ymirapp/php-runtime/commit/d315688659c6bbaced5f0e3a43d24b90bac5f29d))
* Upgrade to phpstan v1 ([f20d8c8](https://github.com/ymirapp/php-runtime/commit/f20d8c85720a43eea59bd2b1426a16af10d1796f))

## [1.19.3](https://github.com/ymirapp/php-runtime/compare/v1.19.2...v1.19.3) (2026-05-16)


### Bug Fixes

* Flush streamed sqs process output ([56fa193](https://github.com/ymirapp/php-runtime/commit/56fa193d8faa23baa96ad6f3805d62f1969a3c6c))

## [1.19.2](https://github.com/ymirapp/php-runtime/compare/v1.19.1...v1.19.2) (2026-05-16)


### Bug Fixes

* Stream sqs queue process output ([866185d](https://github.com/ymirapp/php-runtime/commit/866185dc9f50f5bdf2e10183d688889e58fd8e23))


### Dependency Changes

* Updated composer dependencies ([374f1cf](https://github.com/ymirapp/php-runtime/commit/374f1cf04866502d297db5712aa917eeba41fb6f))
* Upgrade imagick to 7.1.2-22 ([afce65a](https://github.com/ymirapp/php-runtime/commit/afce65ab2e9c3c194553122cb72e86e173661f1f))
* Upgrade relay to 0.22.0 ([8e1ddae](https://github.com/ymirapp/php-runtime/commit/8e1ddaeea55cecc40f79428294f9a11afcd8dfa5))
* Upgrade sqlite to 3.53.1 ([bf76b7c](https://github.com/ymirapp/php-runtime/commit/bf76b7cd298cd42a1672a4a7b1998e978b48b303))
* Upgrade to php 8.2.31 ([c5bf53c](https://github.com/ymirapp/php-runtime/commit/c5bf53c9c4c8e5e0bbb29a79dd13cee0fd214aa2))
* Upgrade to php 8.3.31 ([3955008](https://github.com/ymirapp/php-runtime/commit/39550082e2d8a422b70db8f63a445c9171eb2ac2))
* Upgrade to php 8.4.21 ([a5c130f](https://github.com/ymirapp/php-runtime/commit/a5c130f20683c8fad99f2410b7d2922e20520261))
* Upgrade to php 8.5.6 ([89bfee7](https://github.com/ymirapp/php-runtime/commit/89bfee7246eeb66ec9ee602b15843547f77b3fad))

## [1.19.1](https://github.com/ymirapp/php-runtime/compare/v1.19.0...v1.19.1) (2026-03-30)


### Bug Fixes

* Set openssl ca trust store for php 7.2-8.0 images ([ba6fdc1](https://github.com/ymirapp/php-runtime/commit/ba6fdc1a76aa860e364b01c6bbbb19142916aaaf))

## [1.19.0](https://github.com/ymirapp/php-runtime/compare/v1.18.3...v1.19.0) (2026-03-23)


### Features

* Add acorn sqs handler ([5fae426](https://github.com/ymirapp/php-runtime/commit/5fae42623c0cc442642dfad4b1b81494ebbdee07))

## [1.18.3](https://github.com/ymirapp/php-runtime/compare/v1.18.2...v1.18.3) (2026-03-09)


### Bug Fixes

* Add php-fpm `log_limit` to prevent long stderr messages from being truncated ([c28ee5f](https://github.com/ymirapp/php-runtime/commit/c28ee5ff6231f9d9136ef9ff60e1d66fa3086c64))
* Restart php-fpm and retry once after fastcgi connect failure ([5cb8808](https://github.com/ymirapp/php-runtime/commit/5cb880870451cb63ba0c34c03a6168470070f28d))
* Write php-fpm errors to stderr ([e24bd86](https://github.com/ymirapp/php-runtime/commit/e24bd862bedcfc4b3d24e90fba52ae65f5b59cf1))

## [1.18.2](https://github.com/ymirapp/php-runtime/compare/v1.18.1...v1.18.2) (2026-03-07)


### Bug Fixes

* Force `APP_RUNNING_IN_CONSOLE=true` when initializing laravel-based applications ([3d9f490](https://github.com/ymirapp/php-runtime/commit/3d9f490c67ba8b6f05ee0249a181acb2458521ef))

## [1.18.1](https://github.com/ymirapp/php-runtime/compare/v1.18.0...v1.18.1) (2026-03-07)


### Bug Fixes

* Remove `--no-ansi` from radicle acorn cache initialization command ([a76ab05](https://github.com/ymirapp/php-runtime/commit/a76ab052af166748e721b6e7e321c7b4e4cfad83))
* Throw exception if we're unable to connect to php-fpm ([32e7843](https://github.com/ymirapp/php-runtime/commit/32e78432a20bb67aa1cd227546f62bad0f031023))

## [1.18.0](https://github.com/ymirapp/php-runtime/compare/v1.17.3...v1.18.0) (2026-03-04)


### Features

* Add `PhpScriptApplication` as a `_HANDLER`-based fallback ([5a7b69d](https://github.com/ymirapp/php-runtime/commit/5a7b69d513f9052904d1cc175ef858df35387900))
* Add event handler for laravel ([67d6040](https://github.com/ymirapp/php-runtime/commit/67d60404742462a47935a34e67a846504e545f16))
* Add laravel handler for sqs events ([51ae7a6](https://github.com/ymirapp/php-runtime/commit/51ae7a61c8809841895baf15643062852f194e90))
* Add postgres extension ([7b57af1](https://github.com/ymirapp/php-runtime/commit/7b57af1410555a50dea06ea3e017f8466c2bfd78))
* Add queue function runtime ([74c440e](https://github.com/ymirapp/php-runtime/commit/74c440e8483601b22e844ff5e86a509ff7cbb734))
* Add request and trace id to http requests and responses ([3cd567d](https://github.com/ymirapp/php-runtime/commit/3cd567d110b463752c5fc8f13d85845e7c8795bb))
* Create a dynamic timeout for php-fpm based on remaining execution time ([6cd929f](https://github.com/ymirapp/php-runtime/commit/6cd929febbee99be01e5015f54679d04554d4ccc))
* Disable jit and switch to storing opcode in a file cache ([5c774cc](https://github.com/ymirapp/php-runtime/commit/5c774ccb841ad03485878936aba87f0b619be108))
* Set command timeout based on remaining time ([8c594ff](https://github.com/ymirapp/php-runtime/commit/8c594fff3aa8576e5b4ec23f76bd7b1d3f5f06fb))
* Switch to `lru` for relay `eviction_policy` ([838bf8f](https://github.com/ymirapp/php-runtime/commit/838bf8f9a98671a403520c3dfd9e55aa29ff9de7))
* Switch to using `CloudWatchFormatter` from ymir monolog library ([6f1eeb3](https://github.com/ymirapp/php-runtime/commit/6f1eeb358237a324c6544adaa74c0d3b0a18c79f))


### Bug Fixes

* Make `memory_limit` match the maximum allowed memory for lambdas ([b18fc2f](https://github.com/ymirapp/php-runtime/commit/b18fc2fb21be1c8a72f62029fcd2cd982e5bf56f))


### Dependency Changes

* Updated composer dependencies ([7fcaab7](https://github.com/ymirapp/php-runtime/commit/7fcaab793fcff9b61bd63df93e5e7d9dab707b9c))
* Upgrade sqlite to 3.51.2 ([c976911](https://github.com/ymirapp/php-runtime/commit/c976911e2b6d656e51394aa55a2b943246ea9a4c))
* Upgrade to monolog 2.0 ([59f7150](https://github.com/ymirapp/php-runtime/commit/59f7150bcd5e0866c4b21860b9bbb9558abe27d6))
* Upgrade to php 8.3.30 ([3d89271](https://github.com/ymirapp/php-runtime/commit/3d892714e8350590ea47c49a7194ab5cc3e367ff))
* Upgrade to php 8.4.18 ([997e4ab](https://github.com/ymirapp/php-runtime/commit/997e4ab7dda2cee0f85139da0204ec7d02764684))
* Upgrade to php 8.5.3 ([a7d9143](https://github.com/ymirapp/php-runtime/commit/a7d91435ce5623f5afbeda27878e26aaac9a65f8))

## [1.17.3](https://github.com/ymirapp/php-runtime/compare/v1.17.2...v1.17.3) (2026-01-24)


### Bug Fixes

* Preserve `src` and `templates` directory structure in zip layers ([0ce0e58](https://github.com/ymirapp/php-runtime/commit/0ce0e583c2036e85abff8ce1668c3b8e2eb95050))

## [1.17.2](https://github.com/ymirapp/php-runtime/compare/v1.17.1...v1.17.2) (2026-01-23)


### Bug Fixes

* Reduce zip layer size ([2953929](https://github.com/ymirapp/php-runtime/commit/2953929ccf4a91b2c4431443bae0ec28a1d94e83))

## [1.17.1](https://github.com/ymirapp/php-runtime/compare/v1.17.0...v1.17.1) (2026-01-23)


### Bug Fixes

* Fix missing transient dependencies and autoloader paths in zip layers ([20088de](https://github.com/ymirapp/php-runtime/commit/20088de7524a059ea4e578b97829562f98e2ffec))

## [1.17.0](https://github.com/ymirapp/php-runtime/compare/v1.16.0...v1.17.0) (2026-01-08)


### Features

* Upgrade to al2023 ([581a307](https://github.com/ymirapp/php-runtime/commit/581a3072ec85e64b55bb36aef36bf5c1846cf016))

## [1.16.0](https://github.com/ymirapp/php-runtime/compare/v1.15.4...v1.16.0) (2026-01-07)


### Features

* Add php 8.5 ([28eb125](https://github.com/ymirapp/php-runtime/commit/28eb1252f8e8761f4f2d78e4c02ee9374a480267))


### Bug Fixes

* Kill the lambda container if we get a `ReadFailedException` exception ([1c95fcb](https://github.com/ymirapp/php-runtime/commit/1c95fcb1e07b6d40a6fcaab10c984cc0346c17f3))


### Dependency Changes

* Upgrade imagick extension to 3.8.1 ([a9a710b](https://github.com/ymirapp/php-runtime/commit/a9a710b53a26ba6b248ebd79f8a76defb070da0c))
* Upgrade imagick to 7.1.2-12 ([83500ed](https://github.com/ymirapp/php-runtime/commit/83500ed1c0bc24a88ab7d5b79ee12a360c1334c8))
* Upgrade relay to 0.20.0 ([758a083](https://github.com/ymirapp/php-runtime/commit/758a0834b8217cd97eaaa45c5d82ecd58e456aa5)), closes [#35](https://github.com/ymirapp/php-runtime/issues/35)
* Upgrade sqlite to 3.51.1 ([10c244c](https://github.com/ymirapp/php-runtime/commit/10c244c48c3ac160aec03ca6518666006bd968ca))
* Upgrade to php 8.1.34 ([9e6343c](https://github.com/ymirapp/php-runtime/commit/9e6343c01af3014e6e1d1f9685c27c6024f7e131))
* Upgrade to php 8.2.30 ([40802b0](https://github.com/ymirapp/php-runtime/commit/40802b026636905290e3401be2c35c688a79f0a8))
* Upgrade to php 8.3.29 ([6dcac6e](https://github.com/ymirapp/php-runtime/commit/6dcac6ec67e932011c0186976189ceb539aad11d))
* Upgrade to php 8.4.16 ([a944801](https://github.com/ymirapp/php-runtime/commit/a9448017fa7bbd7aa97b749605fdeb0b086bb9a4))

## [1.15.4](https://github.com/ymirapp/php-runtime/compare/v1.15.3...v1.15.4) (2025-11-16)


### Dependency Changes

* Updated composer dependencies ([2ceca69](https://github.com/ymirapp/php-runtime/commit/2ceca698ff5d3bc47596f21c6ae60f8f5d849c33))
* Upgrade curl to 8.17.0 ([8a7781a](https://github.com/ymirapp/php-runtime/commit/8a7781ab1451cbeb31f6e3b10b89be935ac18ea5))
* Upgrade imagick to 7.1.2-8 ([efe1a49](https://github.com/ymirapp/php-runtime/commit/efe1a498c1a6ae9a05f4d4629297d0641ef207e8))
* Upgrade nghttp2 to 1.66.0 ([fe8b590](https://github.com/ymirapp/php-runtime/commit/fe8b590dca8bc39107e382dd19ed9a52e22d428b))
* Upgrade php versions ([e94cbaa](https://github.com/ymirapp/php-runtime/commit/e94cbaa7e4b17c68c57d15aeb7a0aa976431d065))
* Upgrade relay to 0.12.1 ([8e61f4d](https://github.com/ymirapp/php-runtime/commit/8e61f4d3d73c0673b29bc235c3efdc67e113737d))
* Upgrade sqlite to 3.51.0 ([99c93e0](https://github.com/ymirapp/php-runtime/commit/99c93e050324c7ae4089baac42c9c4933f93fc32))

## [1.15.3](https://github.com/ymirapp/php-runtime/compare/v1.15.2...v1.15.3) (2025-08-23)


### Dependency Changes

* Updated composer dependencies ([ab7968c](https://github.com/ymirapp/php-runtime/commit/ab7968c78179ca2c18945c53ab59f32fc903a201))
* Upgrade curl to 8.15.0 ([21a750c](https://github.com/ymirapp/php-runtime/commit/21a750c691909a85b782cead845b0a7748b0be41))
* Upgrade imagick to 7.1.2-1 ([f88bd21](https://github.com/ymirapp/php-runtime/commit/f88bd21fcf5ab7e57781afb202575a53381726f2))
* Upgrade libde265 to 1.0.16 ([8f76dca](https://github.com/ymirapp/php-runtime/commit/8f76dca04b93b973aa0d89ea199f6fb66247ebce))
* Upgrade libwebp to 1.6.0 ([0acc651](https://github.com/ymirapp/php-runtime/commit/0acc6510cfce23a0a0c9a13895e887036a04a571))
* Upgrade nghttp2 to 1.66.0 ([d4328b6](https://github.com/ymirapp/php-runtime/commit/d4328b69760b0b1fff88df20d503c1596407d2f6))
* Upgrade php versions ([688b610](https://github.com/ymirapp/php-runtime/commit/688b610b311d754e0624310a7c50e9983222a894))
* Upgrade relay to 0.11.1 ([ffca105](https://github.com/ymirapp/php-runtime/commit/ffca105f00c45e4babd228d47081ca9651da20f0))
* Upgrade sqlite to 3.50.4 ([1aa0a61](https://github.com/ymirapp/php-runtime/commit/1aa0a618ed4537a3ea5a98a94f67f3c501457f04))

## [1.15.2](https://github.com/ymirapp/php-runtime/compare/v1.15.1...v1.15.2) (2025-06-27)


### Dependency Changes

* Upgrade curl to 8.14.1 ([17a6a2c](https://github.com/ymirapp/php-runtime/commit/17a6a2cf3cfc1d1c3c98122b752e6bb5920e5758))
* Upgrade imagick extension to 3.8.0 ([5091e1f](https://github.com/ymirapp/php-runtime/commit/5091e1f41b0cd3b262b0f958f8ceccd41e6c5e48))
* Upgrade imagick to 7.1.1-47 ([6e92b87](https://github.com/ymirapp/php-runtime/commit/6e92b8728c2c5b3c0e8efc067dcf34687ca19722))
* Upgrade libzip to 1.11.4 ([0c0ae5d](https://github.com/ymirapp/php-runtime/commit/0c0ae5d763e3eb6266843cc4176f1fd490fc13a7))
* Upgrade relay to 0.11.0 ([239fad2](https://github.com/ymirapp/php-runtime/commit/239fad2a72e7c986a4fa0ef48f9cca585e530b58))

## [1.15.1](https://github.com/ymirapp/php-runtime/compare/v1.15.0...v1.15.1) (2025-06-24)


### Bug Fixes

* `jit_buffer_size` cannot go over 128M on aarch64 ([6ae62bb](https://github.com/ymirapp/php-runtime/commit/6ae62bbcd47d3ca6ce593e501483dda9bb276463))

## [1.15.0](https://github.com/ymirapp/php-runtime/compare/v1.14.0...v1.15.0) (2025-04-15)


### Features

* Don't display deprecation notices when the runtime is starting ([abd781b](https://github.com/ymirapp/php-runtime/commit/abd781beacb6e720312fe4c9103d85c3dcce93db))


### Bug Fixes

* Revert `jit` option to default `tracing` mode ([39d0fff](https://github.com/ymirapp/php-runtime/commit/39d0fffe21389165eb01387ed07036f10c228639))

## [1.14.0](https://github.com/ymirapp/php-runtime/compare/v1.13.0...v1.14.0) (2025-03-22)


### Features

* Add event handler for radicle ([c0bde25](https://github.com/ymirapp/php-runtime/commit/c0bde25e613593d9e692af4fd74a2bd54fb922d3))
* Turn on jit opcache for all php 8 releases ([12ac157](https://github.com/ymirapp/php-runtime/commit/12ac1575ed93e16052a3c57f742de36851a5efe1))

## [1.13.0](https://github.com/ymirapp/php-runtime/compare/v1.12.4...v1.13.0) (2025-01-21)


### Features

* Add php 8.4 ([ff14738](https://github.com/ymirapp/php-runtime/commit/ff1473811243a5241763832c7c0cdb072f3690a1))
* Switch to compiling libwebp ([4014aa9](https://github.com/ymirapp/php-runtime/commit/4014aa921c9fe4f4ce39e89e2c8c2d22dd988f52))
* Switch to compiling sqlite ([ea275ae](https://github.com/ymirapp/php-runtime/commit/ea275ae37fa8917f2a3fc70e401a032e5d178795))
* Switch to compiling zlib ([e2ebd02](https://github.com/ymirapp/php-runtime/commit/e2ebd02757c1a43fbd3291b1429f64cfcc6786e7))


### Bug Fixes

* Downgrade libheif to fix build issue ([8448abc](https://github.com/ymirapp/php-runtime/commit/8448abc9a0594ef42d1c06adc1281608677fc774))
* Downgrade libxml2 version to fix pear install on older php versions ([796b13c](https://github.com/ymirapp/php-runtime/commit/796b13c95e4e25caf0c8fa9e0b009efdd71ec699))
* Fix broken libsodium build ([1cd905f](https://github.com/ymirapp/php-runtime/commit/1cd905f60bbef1bced5de87feff3505e25262ec5))

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
