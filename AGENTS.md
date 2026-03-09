# Agent Guide: Ymir PHP Runtime

This repository contains the Ymir PHP runtime for AWS Lambda. It provides the necessary infrastructure to run PHP applications (WordPress, Laravel, etc.) on Lambda, including support for FastCGI/PHP-FPM.

## Development Commands

### Build & Setup
- **Install Dependencies:** `composer install`

### Quality Control (Linting & Static Analysis)
- **Check Code Style:** `composer php-cs-fixer` (dry-run)
- **Fix Code Style:** `composer php-cs-fixer:fix` (automatically applies fixes)
- **Static Analysis:** `composer phpstan` (PHPStan level 9)
- **Generate Baseline:** `composer phpstan:generate-baseline` (never run this without approval)
- **Rector (Refactoring):** `composer rector` (dry-run)
- **Apply Rector Fixes:** `composer rector:fix`

### Testing
- **Run All Unit Tests:** `composer tests:unit`
- **Run a Single Test File:** `composer tests:unit -- tests/Unit/Path/To/Test.php`
- **Run a Specific Test Method:** `composer tests:unit -- --filter testMethodName`

---

## Code Style & Standards

These are extra rules not covered by PHP-CS-Fixer and Rector.

- Always import classes. Never use the full class name.

---

## Architecture Overview

### Runtime Loop
The runtime operates in a loop, fetching the next invocation from the Lambda Runtime API, handling it, and sending the response back. The core logic resides in `src/AbstractRuntime.php`.

### Event Handlers
Events are processed by handlers implementing `LambdaEventHandlerInterface`.
- **Location:** `src/Lambda/Handler/`
- **Http Handlers:** Special handlers for WordPress, Laravel, Bedrock, and generic PHP scripts. They often inherit from `AbstractHttpRequestEventHandler`.
- **Sqs Handlers:** Handlers for SQS events, inheriting from `AbstractSqsHandler`.
- **Handler Collection:** `LambdaEventHandlerCollection` manages multiple handlers and finds the first one that can handle a given event.

### FastCGI & PHP-FPM
For website runtimes, the runtime starts a PHP-FPM process and communicates with it via FastCGI.
- **FastCGI Client:** Uses `hollodotme/fast-cgi-client`.
- **PhpFpmProcess:** Manages the lifecycle of the PHP-FPM process (start, stop, health checks). See `src/FastCgi/PhpFpmProcess.php`.

### Runtime Context
The `RuntimeContext` class centralizes access to environment variables, logger, and other shared services like `LambdaClient` and `SsmClient`.

### Runtime Build
Docker based runtime build
- **Location:** `runtime/`

---

## Testing Strategy

### Unit Tests
- **Location:** `tests/Unit`
- **Mocking:** Use traits found in `tests/Mock` to facilitate mocking common dependencies.
    - `LoggerMockTrait` for `Logger`
    - `LambdaRuntimeApiClientMockTrait` for `RuntimeApiClient`
    - `LambdaClientMockTrait` for `LambdaClient`
- **PHPUnit Version:** PHPUnit 8 is used. Ensure tests are compatible with this version.

### Test Patterns
- Always test for both successful outcomes and expected exceptions.
- Use `getFunctionMock` from `FunctionMockTrait` to mock global PHP functions like `getenv`, `curl_init`, etc.
- Mock objects should be created using the provided traits whenever possible to maintain consistency.

---

## Implementation Rules for Agents

1. **Verify PSR-4:** Ensure new classes are in the correct namespace under `Ymir\Runtime` (`src/`) or `Ymir\Runtime\Tests` (`tests/`).
2. **Strict Typing:** Always include `declare(strict_types=1);` and use property, parameter, and return types.
3. **Run Linting:** Always run `composer php-cs-fixer:fix` and `composer rector:fix` after modifying code.
4. **Run PHPStan:** Always run `composer phpstan` after changes to ensure type safety. Never fix a PHPStan issue with a comment block.
5. **Mocking:** When writing tests, check `tests/Mock` first to see if a mock trait already exists for the service you are testing.
6. **Environment Variables:** Handle Lambda environment variables via `RuntimeContext` or `getenv()` where appropriate.
7. **No Dependencies:** Avoid adding new dependencies unless absolutely necessary and approved.
8. **Yoda Style:** Maintain Yoda style in all comparisons.
9. **Look around:** Scan nearby files (in the same namespace or unit test files) when creating a new file to figure out the conventions
10. **One level of indentation:** Stick to one level of indentation unless it's impossible to do so. Nested statements should be avoided.
