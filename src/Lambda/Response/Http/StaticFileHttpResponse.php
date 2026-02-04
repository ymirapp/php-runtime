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

namespace Ymir\Runtime\Lambda\Response\Http;

use Mimey\MimeTypes;
use Mimey\MimeTypesInterface;
use Ymir\Runtime\Exception\FileNotReadableException;

/**
 * A Lambda response for a static file.
 */
class StaticFileHttpResponse extends HttpResponse
{
    /**
     * Constructor.
     */
    public function __construct(string $filePath, MimeTypesInterface $mimeTypes = null)
    {
        $content = file_get_contents($filePath);

        if (!is_string($content)) {
            throw new FileNotReadableException(sprintf('Unable to get the contents of "%s"', $filePath));
        }

        $contentType = ($mimeTypes ?? new MimeTypes())->getMimeType(pathinfo($filePath, PATHINFO_EXTENSION)) ?? 'text/plain';

        parent::__construct($content, ['Content-Type' => [$contentType]], 200, '2.0');
    }
}
