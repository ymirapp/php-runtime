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

/**
 * A Lambda response for a 4XX/5XX HTTP responses.
 */
abstract class AbstractErrorHttpResponse extends HttpResponse
{
    /**
     * Constructor.
     */
    public function __construct(string $message, int $statusCode, string $templatesDirectory = '')
    {
        if (empty($templatesDirectory) && getenv('LAMBDA_TASK_ROOT')) {
            $templatesDirectory = '/opt/templates';
        }

        $body = '';
        $template = $templatesDirectory.'/error.html.php';

        if (file_exists($template)) {
            ob_start();

            include $template;

            $body = (string) ob_get_clean();
        }

        parent::__construct($body, [], $statusCode, '2.0');
    }
}
