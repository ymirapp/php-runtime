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

namespace Ymir\Runtime\Lambda\Response;

/**
 * A Lambda response for a static file.
 */
class StaticFileResponse implements ResponseInterface
{
    /**
     * The path to the file that we're sending back to the Lambda runtime API.
     *
     * @var string
     */
    private $filePath;

    /**
     * Constructor.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData(): array
    {
        $file = file_get_contents($this->filePath);

        if (!is_string($file)) {
            throw new \RuntimeException(sprintf('Unable to get the contents of "%s"', $this->filePath));
        }

        return [
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'headers' => ['Content-Type' => mime_content_type($this->filePath)],
            'body' => base64_encode($file),
        ];
    }
}
