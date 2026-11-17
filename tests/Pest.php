<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Project\ProjectDetector;
use Aluisio\PhpQualityPack\Support\Filesystem;

function fixturePath(string $name): string
{
    return __DIR__.'/Fixtures/'.$name;
}

function detectProject(string $directory): Project
{
    return (new ProjectDetector)->detect($directory);
}

function temporaryProject(string $fixture): string
{
    $directory = sys_get_temp_dir().'/pqp-tests/'.uniqid($fixture.'-', true);

    (new Filesystem)->copyDirectory(fixturePath($fixture), $directory);

    return $directory;
}

function removeDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($directory);
}
