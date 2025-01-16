<?php

/*
 * This file is part of JoliCode's structured-data-validator-demo project.
 *
 * (c) jolicode.com <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace frontend;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function docker\docker_compose_run;

#[AsTask(description: 'Builds assets')]
function build(): void
{
    io()->title('Building assets');

    docker_compose_run('bin/console tailwind:build', workDir: '/var/www/application');
    docker_compose_run('bin/console asset-map:compile', workDir: '/var/www/application');
}

#[AsTask(description: 'Builds and watches assets', aliases: ['watch'])]
function watch(): void
{
    io()->title('Watching assets...');

    docker_compose_run('bin/console tailwind:build --watch', context()->toInteractive(), workDir: '/var/www/application');
}
