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

use Mimey\MimeTypes;
use Mimey\MimeTypesInterface;

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
     * MIME type handling library.
     *
     * @var MimeTypesInterface
     */
    private $mimeTypes;

    /**
     * Constructor.
     */
    public function __construct(string $filePath, MimeTypesInterface $mimeTypes = null)
    {
        $this->filePath = $filePath;
        $this->mimeTypes = $mimeTypes ?? new MimeTypes();
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

        $contentType = null;
        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);

        if (is_string($extension)) {
            $contentType = $this->mimeTypes->getMimeType($extension);
        }

        return [
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'headers' => ['Content-Type' => $contentType ?? 'text/plain'],
            'body' => base64_encode($file),
        ];
    }
}
