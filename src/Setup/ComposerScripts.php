<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Support\Filesystem;
use stdClass;

final class ComposerScripts
{
    public const SCRIPTS = [
        'quality' => 'pqp check',
        'quality:fix' => 'pqp fix',
    ];

    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * @return list<string>
     */
    public function register(string $composerFile): array
    {
        if (! $this->filesystem->exists($composerFile)) {
            return [];
        }

        $manifest = json_decode($this->filesystem->read($composerFile));

        if (! $manifest instanceof stdClass) {
            return [];
        }

        $scripts = isset($manifest->scripts) && $manifest->scripts instanceof stdClass ? $manifest->scripts : new stdClass;
        $added = [];

        foreach (self::SCRIPTS as $name => $command) {
            if (! property_exists($scripts, $name)) {
                $scripts->{$name} = $command;
                $added[] = $name;
            }
        }

        if ($added === []) {
            return [];
        }

        $manifest->scripts = $scripts;

        $this->filesystem->write(
            $composerFile,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        return $added;
    }
}
