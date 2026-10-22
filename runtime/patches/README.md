# Legacy PHP security backports (YMIR-127)

Pinned minimal EOL mitigations for CVE-2026-109618 / GHSA-w8hw-w9fx-j79w
and CVE-2026-109619 / GHSA-6wmf-4rv5-p3rp. These do not restore upstream
support or include a distro's other historical security fixes.

## Inputs and application order

| Pristine source | Patches, in order |
| --- | --- |
| PHP 7.2.34 | `php-7.2-zstr-max-len.patch`, `php-7.2-security.patch`, `php-7.2-7.3-multipart-limit.patch` |
| PHP 7.3.33 | `php-7.3-zstr-max-len.patch`, `php-7.3-security.patch`, `php-7.2-7.3-multipart-limit.patch` |
| PHP 7.4.33 | `php-7.4-security.patch` (PHP security team) |
| PHP 8.0.30 | `php-8.0-security.patch` (PHP security team) |
| PHP 8.1.34 | `php-8.1-security.patch` (PHP security team) |

The five security patches and two string-limit prerequisite patches are
byte-for-byte copies of the sources below.
`SHA256SUMS` pins all patch and retained provenance bytes:

```sh
(cd runtime/patches && shasum -a 256 --check SHA256SUMS)
```

The Dockerfiles copy only the applicable patches into the build stage and
apply them after extraction, before `buildconf`. `git` is already installed;
`git apply` does not use fuzzy matching, does not partially accept a patch,
and returns failure for rejected hunks. All three 7.2/7.3 applications are
chained with `&&`, so failure of any patch stops the build. No new package or
network-fetched patch is required. Patches/provenance are not copied into
`/opt` or the exported layer.

The repository build context must allow `runtime/patches/` through
`.dockerignore`; otherwise these `COPY` instructions deliberately fail.

### Official PHP security-team patches

Downloaded unchanged from these immutable commit patch URLs:

- PHP 7.4: https://github.com/php/php-src/commit/712e60f9edfd03f0960fc4fc3c2c375f42b90aa2.patch
- PHP 8.0: https://github.com/php/php-src/commit/82f8f31b16a035c1b67cc253e79f7cc288f4cc20.patch
- PHP 8.1: https://github.com/php/php-src/commit/1d7dec178a094e6e75b229ce22754045f8c6070a.patch

These are the exceptional EOL backports linked by PHP's October 21, 2026
security announcement: https://www.php.net/archive/2026.php.
Commit/patch identity was checked over HTTPS, not by a Git commit signature.

### Sury PHP 7.2/7.3 patches

Use source patches, not Debian binaries or the entire distro patch series.

- PHP 7.2 package `7.2.34-66+0~20261021.122+debian13~1.gbpe0bcd4`:
  - Archive: https://packages.sury.org/php/pool/main/p/php7.2/php7.2_7.2.34-66%2B0~20261021.122%2Bdebian13~1.gbpe0bcd4.debian.tar.xz
  - Archive SHA-256: `4a5a49d808a4d0cd4d32709fbf471519e8c04d81fbc8f237baae073a87454ee9`
  - Security member: `debian/patches/0159-Avoid-state-corruption-via-bailout.patch`
  - Prerequisite member: `debian/patches/0135-backport-ZSTR_MAX_LEN.patch`
  - Adjacent `.dsc` SHA-256: `a06c4b5deeeed7ac81be430c95b74a9f456d21e19ca8d1aae8a2f319d4b1dcfe`
- PHP 7.3 package `7.3.33-35+0~20261021.144+debian13~1.gbpecf76f`:
  - Archive: https://packages.sury.org/php/pool/main/p/php7.3/php7.3_7.3.33-35%2B0~20261021.144%2Bdebian13~1.gbpecf76f.debian.tar.xz
  - Archive SHA-256: `0c65625f2cbd18749891f7a0bba653c7eabe5864155036cac9407508560eb983`
  - Security member: `debian/patches/0139-Avoid-state-corruption-via-bailout.patch`
  - Prerequisite member: `debian/patches/0107-backport-ZSTR_MAX_LEN.patch`
  - Adjacent `.dsc` SHA-256: `c22d4541d5264ec53918b7d9b70b74121cdfeef1c6974b9fe4e9ad552b310575`

All four patches are listed in their archive's `debian/patches/series`.

#### Verified signature chain

Verified October 22, 2026 using GnuPG, SHA-256, and archive member comparison:

1. `provenance/sury-keyring.gpg` came from https://packages.sury.org/php/apt.gpg.
   Primary key fingerprint: `15058500A0235D97F5D10063B188E2B695BD4743`
   (`DEB.SURY.ORG Automatic Signing Key <deb@sury.org>`).
2. `provenance/sury-trixie-InRelease` came from
   https://packages.sury.org/php/dists/trixie/InRelease, dated
   **2026-10-22 12:01:18 UTC**. `gpgv` returned a good signature and
   `VALIDSIG` for that primary fingerprint.
3. Its SHA-256 entry authenticates `provenance/sury-trixie-Sources.xz`, from
   https://packages.sury.org/php/dists/trixie/main/source/Sources.xz.
4. The `php7.2` and `php7.3` records authenticate the exact archive and `.dsc`
   hashes above. Both archives also match their `.dsc` SHA-256 entries, and
   their extracted security/prerequisite patch bytes match `SHA256SUMS`.

The `.dsc` files are **unsigned**, not independently OpenPGP-signed. The
verified signature is on `InRelease`, covering them through the source
index. The trust anchor is Sury's HTTPS-distributed key, not an independently
established personal/web-of-trust identity. Retained index/signature bytes
are historical provenance, not a current APT freshness check.

Replay the signature check (from the repository root):

```sh
gpgv --keyring "$PWD/runtime/patches/provenance/sury-keyring.gpg" \
    runtime/patches/provenance/sury-trixie-InRelease
```

### Pristine PHP sources

Archives are `https://www.php.net/distributions/php-<version>.tar.gz`.
SHA-256 was rechecked against the corresponding `source` entry at
`https://www.php.net/releases/index.php?json&version=<version>`:

| Version | Archive SHA-256 |
| --- | --- |
| 7.2.34 | `8b2777c741e83f188d3ca6d8e98ece7264acafee86787298fae57e05d0dddc78` |
| 7.3.33 | `9a369c32c6f52036b0a890f290327f148a1904ee66aa56e2c9a7546da6525ec8` |
| 7.4.33 | `5a2337996f07c8a097e03d46263b5c98d2c8e355227756351421003bea8f463e` |
| 8.0.30 | `449d2048fcb20a314d8c218097c6d1047a9f1c5bb72aa54d5d3eba0a27a4c80c` |
| 8.1.34 | `3c5b060ec8e0d5dd1d8237823f3161cc8bc5342aab3c46893eba9857759c4bfa` |

## The string-limit prerequisite

The Sury security patches use `ZSTR_MAX_LEN`, also absent from pristine
7.2.34 and 7.3.33. A minimal AL2023 arm64 build of 7.2 initially failed in
`Zend/zend_vm_execute.h` with `ZSTR_MAX_LEN undeclared`; clean patch checks
did not expose this prerequisite.

The two `php-7.x-zstr-max-len.patch` files add only `ZSTR_MAX_OVERHEAD` and
`ZSTR_MAX_LEN` (plus a blank line) to `Zend/zend_string.h`. The definitions
match pristine PHP 7.4.33 exactly and account for header/alignment overhead
before the new concatenation overflow checks. They are unchanged Sury
archive members from the same authenticated packages, not a wholesale
distro patch series.

## The pristine-source multipart adaptation

Sury's headers say `max_multipart_body_parts` was omitted because the distro
already had it. Neither pristine PHP 7.2.34 nor 7.3.33 has it. Applying only
the Sury patch therefore leaves a real hardening gap despite clean hunks.

`php-7.2-7.3-multipart-limit.patch` restores **only the four omitted hunks**
from official PHP 7.4 commit `712e60f9edfd03f0960fc4fc3c2c375f42b90aa2`.
The hunk bytes, including context, are unchanged; only the patch envelope
is new. Both exact older sources accept them without fuzz after Sury's
patch. Apart from the two string-limit definitions above, no other distro
prerequisites or historical fixes are imported.

- Register `max_multipart_body_parts`, default `-1`, system/per-directory.
- A negative setting derives the limit from `max_input_vars + max_file_uploads`.
- Count each part with Content-Disposition before parameter/body allocation.
- Warn and use the existing `fileupload_done` cleanup when exceeding the
  limit; zero permits no such parts. Earlier accepted parts remain available.

Review checked those registration, initialization, enforcement, and cleanup
sites against the official 7.4 implementation, not just patch applicability.

## Verification and release gates

All five checksum-verified source archives passed, in the order above:

```sh
git apply --check /absolute/path/to/patch
patch --dry-run --batch --fuzz=0 -p1 -i /absolute/path/to/patch
git apply /absolute/path/to/patch
git apply --reverse --check /absolute/path/to/patch
```

Source assertions also checked the four adaptation hunks against official
7.4 bytes, proved the limit absent before/present after on 7.2/7.3, checked
its enforcement before parameter allocation, and checked allocator hard-exit
and session-progress disablement on all five versions, plus the required
string-limit definitions. Applicability and
source inspection alone are not proof of exploit prevention or a bootable
Lambda runtime. Each security patch was also tested with an intentionally
corrupted allocator hunk: `git apply` exited 1 without partially modifying
any source file. A scratch Docker build confirmed the pinned patches are
available through the repository build context.

Minimal AL2023 **linux/arm64** builds of PHP 7.2.34 and 7.3.33 passed
`buildconf --force`, configuration, and `make -j2`, producing CLI, CGI, and
FPM binaries. Configuration was `CFLAGS=-O1 ./configure --disable-all
--enable-cli --enable-cgi --enable-fpm --enable-session --enable-json
--without-pear`. The repository's `runtime/security/verify.php` also passed
against both minimal builds: normal/nested uploads, upload-progress
disablement, explicit/derived multipart limits, warm-worker state reset,
FPM OOM/child replacement/warm recovery, and CLI OOM/failure exit codes.
These used the runtime's FastCGI/process wrappers, but are not full Ymir
image or extension builds.

Before release, require both-architecture builds and
`runtime/test-image.sh <image> <platform>` for all five versions, including
upload/nested-form behavior, multipart boundaries, OOM child replacement and
next-request health, session upload-progress disablement, and CLI/queue
failure behavior. The minimal source checks do not validate extensions,
PHP-FPM lifecycle, complete images, layer exports, or earlier/future CVEs.

### Customer impact and rollback

- **OOM exits the process immediately with status 1**, bypassing normal PHP
  shutdown functions/destructors. An invocation may fail after side effects;
  do not add automatic application retries as part of this patch.
- **PHP session upload-progress tracking is disabled unconditionally**, even
  if its INI setting is enabled. Ordinary uploads remain supported.
- Multipart limits and ignored duplicate/unused MIME headers can change
  malformed or unusually large multipart requests.
- Existing Lambda deployments are not patched retroactively. After separately
  authorized publishing/catalog synchronization, zip deployments need a new
  deploy; container deployments need patched-base rebuilds and redeployment
  (including digest updates where pinned).
- Preserve prior artifacts for recovery. Reverting these changes or deploying
  an old image/layer restores the vulnerabilities; prefer a forward fix and
  explicitly communicate renewed exposure if rollback is unavoidable.

No publishing, tags, catalogs, layer names, image aliases, bootstrap paths,
or PHP versions are changed by these source backports.
