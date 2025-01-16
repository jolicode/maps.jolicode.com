<?php

namespace maps\infra;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Context;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\exit_code;
use function Castor\io;
use function Castor\log;
use function Castor\run;

#[AsTask(description: 'Build the infrastructure')]
function build(
    #[AsOption(description: 'Push new image layers')]
    bool $push = false,
): int {
    $userId = posix_geteuid();

    if ($userId > 256000) {
        $userId = 1000;
    }

    if (0 === $userId) {
        log('Running as root? Fallback to fake user id.', 'warning');
        $userId = 1000;
    }

    if (!isLoggedInGhcr()) {
        if ($push) {
            io()->error('You are not logged in to ghcr.io, so you cannot push the image.');

            return 1;
        }
        io()->warning('You should log in to ghcr.io, so you can pull a prebuilt image.');
    }

    return exit_code(\sprintf(
        'docker build -t %s --build-arg USER_ID=%s --cache-from=type=registry,ref=%s --pull%s %s',
        getImageName(),
        $userId,
        getImageName(),
        $push ? ' --build-arg BUILDKIT_INLINE_CACHE=1 --push' : '',
        realpath(__DIR__ . '/../../infrastructure/maps'),
    ), context: context()->withTimeout(null));
}

#[AsTask(description: 'Open a shell (bash) into the maps container')]
function builder(): void
{
    $c = context()
        ->withTimeout(null)
        ->withTty()
        ->withEnvironment($_ENV + $_SERVER)
        ->withAllowFailure()
    ;
    docker_run('bash', c: $c);
}

function docker_run(
    string $runCommand,
    ?Context $c = null,
    array $additionalVolumes = [],
    ?string $workDir = null,
): Process {
    $c ??= context();
    $c = $c->withTimeout(null);

    $process = run(\sprintf(
        'docker image inspect %s',
        getImageName(),
    ), context: context()->withAllowFailure(true)->withQuiet(true));

    if (false === $process->isSuccessful()) {
        throw new \LogicException(\sprintf('Unable to find %s image. Did you forget to run castor maps:infra:build ?', getImageName()));
    }

    $command = [
        'docker',
        'run',
        '--init',
        '--rm',
        $c->quiet || false === $c->tty && false === $c->pty ? '' : '-i',
        '-t',
        '--network=host',
        \sprintf('-v%s:/home/app/maps:cached', realpath(__DIR__ . '/../../maps')),
    ];

    foreach ($additionalVolumes as $localDirectory => $containerDirectory) {
        $command[] = \sprintf('-v %s:%s', $localDirectory, $containerDirectory);
    }

    if (null !== $workDir) {
        $command[] = '-w';
        $command[] = $workDir;
    }

    $command[] = getImageName();
    $command[] = '/bin/bash';
    $command[] = '-c';
    $command[] = $runCommand;

    return run($command, context: $c);
}

function getImageName(): string
{
    return \sprintf(
        'ghcr.io/jolicode/maps:%s',
        md5_file(realpath(__DIR__ . '/../../infrastructure/maps/Dockerfile'))
    );
}

function isLoggedInGhcr(): bool
{
    return run('docker login ghcr.io', context: context()->withAllowFailure(true)->withPty(false)->withQuiet(true))->isSuccessful();
}
