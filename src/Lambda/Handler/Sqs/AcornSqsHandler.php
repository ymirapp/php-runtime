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

namespace Ymir\Runtime\Lambda\Handler\Sqs;

class AcornSqsHandler extends AbstractIlluminateQueueSqsHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getProcessArguments(): array
    {
        return [
            '/opt/bin/php',
            $this->getRootDirectory().'/bin/wp',
            'acorn',
            'ymir:queue:work',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getQueueName(): string
    {
        return 'Acorn';
    }
}
