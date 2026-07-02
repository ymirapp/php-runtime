<?php

declare(strict_types=1);

/*
 * This file is part of Ymir PHP Runtime.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Runtime\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Application\LaravelApplication;
use Ymir\Runtime\Exception\ApplicationInitializationException;
use Ymir\Runtime\Lambda\Handler\Http\LaravelHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\Sqs\LaravelSqsHandler;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class LaravelApplicationTest extends TestCase
{
    use FunctionMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    private const ENVIRONMENT_VARIABLES = [
        'APP_ENV',
        'APP_KEY',
        'DECRYPTED_ENV_VALUE',
        'LARAVEL_ENV_ENCRYPTION_KEY',
        'YMIR_ENVIRONMENT',
    ];

    private $originalEnvironmentVariables = [];

    private $tempDir;

    private $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/ymir_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->originalEnvironmentVariables = collect(self::ENVIRONMENT_VARIABLES)->mapWithKeys(function (string $environmentVariable): array {
            $value = $this->getEnvironmentVariable($environmentVariable);
            $this->clearEnvironmentVariable($environmentVariable);

            return [$environmentVariable => $value];
        })->all();
    }

    protected function tearDown(): void
    {
        collect($this->temporaryFiles)
            ->filter(static function (string $temporaryFile): bool {
                return file_exists($temporaryFile);
            })
            ->each(static function (string $temporaryFile): void {
                unlink($temporaryFile);
            });

        $this->removeDirectory($this->tempDir);

        collect($this->originalEnvironmentVariables)->each(function (?string $value, string $name): void {
            if (is_string($value)) {
                $this->setEnvironmentVariable($name, $value);

                return;
            }

            $this->clearEnvironmentVariable($name);
        });
    }

    public function testGetQueueHandlers(): void
    {
        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new LaravelApplication($context);
        $handlers = $application->getQueueHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(LaravelSqsHandler::class, $handlers[2]);
    }

    public function testGetWebsiteHandlers(): void
    {
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir, null, $process);

        $application = new LaravelApplication($context);
        $handlers = $application->getWebsiteHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(LaravelHttpEventHandler::class, $handlers[2]);
    }

    public function testInitializeCreatesStorageDirectoriesAndCache(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = $this->getLaravelApplication([
            ['config:cache', '--no-ansi'],
        ]);

        $application->initialize();

        $this->assertDirectoryExists('/tmp/storage/bootstrap/cache');
        $this->assertDirectoryExists('/tmp/storage/framework/cache');
        $this->assertDirectoryExists('/tmp/storage/framework/views');

        $this->removeDirectory('/tmp/storage');
    }

    public function testInitializeDecryptsEnvironmentFileBeforeCreatingCacheWhenEncryptionKeyIsPresent(): void
    {
        $this->setEnvironmentVariable('APP_ENV', 'staging');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        touch($this->tempDir.'/.env.staging.encrypted');

        $decryptCommand = ['env:decrypt', '--env=staging', '--path=/tmp', '--force', '--no-ansi', '--no-interaction'];
        $application = $this->getLaravelApplication([
            $decryptCommand,
            ['config:cache', '--no-ansi'],
        ], [
            function (): void {
                $this->writeTemporaryFile('/tmp/.env.staging', "APP_KEY=base64:decrypted\nDECRYPTED_ENV_VALUE=loaded\n");
            },
        ]);

        $application->initialize();

        $this->assertStringNotContainsString('--key', implode(' ', $decryptCommand));
    }

    public function testInitializeDoesNotRunDecryptCommandWhenEncryptionKeyIsMissing(): void
    {
        $application = $this->getLaravelApplication([
            ['config:cache', '--no-ansi'],
        ]);

        $application->initialize();
    }

    public function testInitializeFallsBackToYmirEnvironmentWhenApplicationEnvironmentIsMissing(): void
    {
        $this->setEnvironmentVariable('YMIR_ENVIRONMENT', 'branch');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        touch($this->tempDir.'/.env.branch.encrypted');

        $application = $this->getLaravelApplication([
            ['env:decrypt', '--env=branch', '--path=/tmp', '--force', '--no-ansi', '--no-interaction'],
            ['config:cache', '--no-ansi'],
        ], [
            function (): void {
                $this->writeTemporaryFile('/tmp/.env.branch', 'APP_KEY=base64:branch');
            },
        ]);

        $application->initialize();
    }

    public function testInitializeLoadsDecryptedVariablesIntoRuntimeEnvironment(): void
    {
        $this->setEnvironmentVariable('APP_ENV', 'testing');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        touch($this->tempDir.'/.env.testing.encrypted');

        $this->getLaravelApplication([
            ['env:decrypt', '--env=testing', '--path=/tmp', '--force', '--no-ansi', '--no-interaction'],
            ['config:cache', '--no-ansi'],
        ], [
            function (): void {
                $this->writeTemporaryFile('/tmp/.env.testing', "APP_KEY=base64:runtime\nDECRYPTED_ENV_VALUE=\"runtime loaded\"\n");
            },
            function (): void {
                $this->assertSame('base64:runtime', $_ENV['APP_KEY']);
                $this->assertSame('base64:runtime', $_SERVER['APP_KEY']);
                $this->assertSame('base64:runtime', getenv('APP_KEY'));
                $this->assertSame('runtime loaded', $_ENV['DECRYPTED_ENV_VALUE']);
                $this->assertSame('runtime loaded', $_SERVER['DECRYPTED_ENV_VALUE']);
                $this->assertSame('runtime loaded', getenv('DECRYPTED_ENV_VALUE'));
            },
        ])->initialize();

        $this->assertSame('base64:runtime', $_ENV['APP_KEY']);
        $this->assertSame('base64:runtime', $_SERVER['APP_KEY']);
        $this->assertSame('base64:runtime', getenv('APP_KEY'));
        $this->assertSame('runtime loaded', $_ENV['DECRYPTED_ENV_VALUE']);
        $this->assertSame('runtime loaded', $_SERVER['DECRYPTED_ENV_VALUE']);
        $this->assertSame('runtime loaded', getenv('DECRYPTED_ENV_VALUE'));
    }

    public function testInitializeThrowsClearExceptionWhenDecryptionFails(): void
    {
        $this->setEnvironmentVariable('APP_ENV', 'staging');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        touch($this->tempDir.'/.env.staging.encrypted');

        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage(sprintf('Failed to decrypt Laravel environment file "%s/.env.staging.encrypted": bad key', $this->tempDir));

        $this->getLaravelApplication([
            ['env:decrypt', '--env=staging', '--path=/tmp', '--force', '--no-ansi', '--no-interaction'],
        ], [], [
            $this->getProcessFailedException('bad key'),
        ])->initialize();
    }

    public function testInitializeThrowsClearExceptionWhenEncryptedFileIsMissing(): void
    {
        $this->setEnvironmentVariable('APP_ENV', 'missing');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage('Laravel environment encryption key was provided');
        $this->expectExceptionMessage(sprintf('%s/.env.missing.encrypted', $this->tempDir));

        $this->getLaravelApplication()->initialize();
    }

    public function testInitializeThrowsExceptionWhenApplicationAndYmirEnvironmentsAreMissing(): void
    {
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage('Unable to determine Laravel environment for encrypted environment file. Set "APP_ENV" or "YMIR_ENVIRONMENT"');

        $this->getLaravelApplication()->initialize();
    }

    public function testInitializeThrowsExceptionWhenProcessFails(): void
    {
        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage('Failed to create Laravel cache');

        $this->getLaravelApplication([
            ['config:cache', '--no-ansi'],
        ], [], [
            $this->getProcessFailedException('cache failed'),
        ])->initialize();
    }

    public function testInitializeUsesApplicationEnvironmentForEncryptedFilePath(): void
    {
        $this->setEnvironmentVariable('APP_ENV', 'preview');
        $this->setEnvironmentVariable('LARAVEL_ENV_ENCRYPTION_KEY', 'secret');

        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage(sprintf('encrypted environment file "%s/.env.preview.encrypted" does not exist', $this->tempDir));

        $this->getLaravelApplication()->initialize();
    }

    public function testPresentReturnsFalseWhenFilesMissing(): void
    {
        $this->assertFalse(LaravelApplication::present($this->tempDir));
    }

    public function testPresentReturnsTrueWhenBothExist(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $this->assertTrue(LaravelApplication::present($this->tempDir));
    }

    private function clearEnvironmentVariable(string $name): void
    {
        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
    }

    private function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) ? $value : null;
    }

    private function getLaravelApplication(array $commands = [], array $callbacks = [], array $exceptions = []): LaravelApplication
    {
        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = $this->getMockBuilder(LaravelApplication::class)
                            ->setConstructorArgs([$context])
                            ->setMethods(['runArtisanCommand'])
                            ->getMock();

        $commands = collect($commands);

        if ($commands->isEmpty()) {
            $application->expects($this->never())->method('runArtisanCommand');

            return $application;
        }

        $commandIndex = 0;
        $invocation = $application->expects($this->exactly($commands->count()))
                                  ->method('runArtisanCommand');
        $invocation->withConsecutive(...$commands->map(static function (array $command): array {
            return [$command];
        })->all());

        $actions = $commands->mapWithKeys(static function (array $command, int $index) use ($callbacks, $exceptions): array {
            return [$index => [
                'callback' => $callbacks[$index] ?? null,
                'exception' => $exceptions[$index] ?? null,
            ]];
        });

        $invocation->willReturnCallback(function (array $arguments) use (&$commandIndex, $actions): void {
            $action = $actions->get($commandIndex, []);
            ++$commandIndex;

            $exception = $action['exception'] ?? null;

            if ($exception instanceof ProcessFailedException) {
                throw $exception;
            }

            $callback = $action['callback'] ?? null;

            if (is_callable($callback)) {
                call_user_func($callback);
            }
        });

        return $application;
    }

    private function getProcessFailedException(string $errorOutput = '', string $output = ''): ProcessFailedException
    {
        $process = $this->getMockBuilder(Process::class)
                        ->setConstructorArgs([['php']])
                        ->setMethods([
                            'getCommandLine',
                            'getErrorOutput',
                            'getExitCode',
                            'getExitCodeText',
                            'getOutput',
                            'getWorkingDirectory',
                            'isOutputDisabled',
                            'isSuccessful',
                        ])
                        ->getMock();

        $process->method('getCommandLine')->willReturn('php');
        $process->method('getErrorOutput')->willReturn($errorOutput);
        $process->method('getExitCode')->willReturn(1);
        $process->method('getExitCodeText')->willReturn('General error');
        $process->method('getOutput')->willReturn($output);
        $process->method('getWorkingDirectory')->willReturn($this->tempDir);
        $process->method('isOutputDisabled')->willReturn(false);
        $process->method('isSuccessful')->willReturn(false);

        return new ProcessFailedException($process);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        collect(array_diff(scandir($dir), ['.', '..']))->each(function (string $file) use ($dir): void {
            $path = sprintf('%s/%s', $dir, $file);

            if (is_dir($path)) {
                $this->removeDirectory($path);

                return;
            }

            unlink($path);
        });

        rmdir($dir);
    }

    private function setEnvironmentVariable(string $name, string $value): void
    {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv(sprintf('%s=%s', $name, $value));
    }

    private function writeTemporaryFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents);

        $this->temporaryFiles[] = $path;
    }
}
