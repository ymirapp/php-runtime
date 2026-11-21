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

namespace Ymir\Runtime\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ymir\Bridge\Monolog\Formatter\CloudWatchFormatter;
use Ymir\Runtime\Logger;
use Ymir\Runtime\ProcessOutputLogger;

class ProcessOutputLoggerTest extends TestCase
{
    /**
     * @var resource
     */
    private $stream;

    protected function setUp(): void
    {
        $_SERVER['AWS_REQUEST_ID'] = 'lambda-request-id';
        $this->stream = fopen('php://memory', 'w+');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['AWS_REQUEST_ID']);
        fclose($this->stream);
    }

    public static function provideOutput(): \Iterator
    {
        yield 'JSON object line' => [
            ["{\"event\":\"http_request\",\"http.status\":200}\n"],
            "{\"event\":\"http_request\",\"http.status\":200}\n",
        ];
        yield 'CloudWatch line with request ID' => [
            ["other-request-id\tWARNING\t[database] slow query\t{\"message\":\"[database] slow query\",\"level\":\"WARNING\"}\n"],
            "other-request-id\tWARNING\t[database] slow query\t{\"message\":\"[database] slow query\",\"level\":\"WARNING\"}\n",
        ];
        yield 'CloudWatch line without request ID' => [
            ["ERROR\tSomething failed\t{\"message\":\"Something failed\",\"level\":\"ERROR\"}\n"],
            "ERROR\tSomething failed\t{\"message\":\"Something failed\",\"level\":\"ERROR\"}\n",
        ];
        $cloudWatchLine = (new CloudWatchFormatter())->format([
            'message' => "Payment\tfailed {order}",
            'level_name' => 'ERROR',
            'context' => ['exception' => new \RuntimeException('Card declined'), 'order' => ['id' => 12]],
            'extra' => ['url' => '/checkout'],
        ]);

        yield 'line written by the CloudWatchFormatter' => [
            [$cloudWatchLine],
            $cloudWatchLine,
        ];
        yield 'JSON object line with surrounding whitespace' => [
            [" {\"event\":\"http_request\"}\r\n"],
            "{\"event\":\"http_request\"}\n",
        ];
        yield 'raw line' => [
            ["PHP Warning:  Undefined variable \$foo in /var/task/index.php on line 3\n"],
            "lambda-request-id\tINFO\tPHP Warning:  Undefined variable \$foo in /var/task/index.php on line 3\t{\"message\":\"PHP Warning:  Undefined variable \$foo in /var/task/index.php on line 3\",\"level\":\"INFO\"}\n",
        ];
        yield 'invalid JSON object line' => [
            ["{not json}\n"],
            "lambda-request-id\tINFO\t{not json}\t{\"message\":\"{not json}\",\"level\":\"INFO\"}\n",
        ];
        yield 'JSON array line' => [
            ["[1,2]\n"],
            "lambda-request-id\tINFO\t[1,2]\t{\"message\":\"[1,2]\",\"level\":\"INFO\"}\n",
        ];
        yield 'tab separated line with an unknown level' => [
            ["id\tVERBOSE\tmessage\t{\"level\":\"VERBOSE\"}\n"],
            "lambda-request-id\tINFO\tid\tVERBOSE\tmessage\t{\"level\":\"VERBOSE\"}\t{\"message\":\"id\\tVERBOSE\\tmessage\\t{\\\"level\\\":\\\"VERBOSE\\\"}\",\"level\":\"INFO\"}\n",
        ];
        yield 'line split across chunks' => [
            ['{"event":"http_request",', "\"http.status\":200}\n"],
            "{\"event\":\"http_request\",\"http.status\":200}\n",
        ];
        yield 'several lines in one chunk' => [
            ["NOTICE: ready to handle connections\n{\"event\":\"http_request\"}\n"],
            "lambda-request-id\tINFO\tNOTICE: ready to handle connections\t{\"message\":\"NOTICE: ready to handle connections\",\"level\":\"INFO\"}\n{\"event\":\"http_request\"}\n",
        ];
        yield 'blank lines' => [
            ["\n", " \n\n"],
            '',
        ];
        yield 'line without a newline' => [
            ['{"event":"console"}'],
            "{\"event\":\"console\"}\n",
        ];
    }

    /**
     * @dataProvider provideOutput
     */
    public function testLogWritesFormattedLinesUnchangedAndFormatsTheRest(array $chunks, string $expected): void
    {
        $processOutput = new ProcessOutputLogger(new Logger(Logger::INFO, $this->stream));

        foreach ($chunks as $chunk) {
            $processOutput->log($chunk);
        }

        $processOutput->flush();

        $this->assertSame($expected, $this->getWrittenOutput());
    }

    public function testLogWritesIncompleteLineLongerThanPhpFpmLogLimit(): void
    {
        $processOutput = new ProcessOutputLogger(new Logger(Logger::INFO, $this->stream));

        $processOutput->log(str_repeat('a', 256000));

        $this->assertSame('', $this->getWrittenOutput());

        $processOutput->log('a');

        $this->assertStringStartsWith("lambda-request-id\tINFO\t".str_repeat('a', 256001)."\t", $this->getWrittenOutput());
    }

    public function testLogWritesNothingBelowLoggerLevel(): void
    {
        $processOutput = new ProcessOutputLogger(new Logger(Logger::WARNING, $this->stream));

        $processOutput->log("{\"event\":\"http_request\"}\nraw line\n");
        $processOutput->flush();

        $this->assertSame('', $this->getWrittenOutput());
    }

    private function getWrittenOutput(): string
    {
        rewind($this->stream);

        return (string) stream_get_contents($this->stream);
    }
}
