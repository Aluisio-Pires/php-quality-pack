<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Ai;

use Aluisio\PhpQualityPack\Support\Filesystem;

final class SkillPublisher
{
    public const DEFAULT_PATH = '.claude/skills';

    private Filesystem $filesystem;

    private string $source;

    public function __construct(?Filesystem $filesystem = null, ?string $source = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
        $this->source = $source ?? dirname(__DIR__, 2).'/resources/skills';
    }

    /**
     * @return list<string>
     */
    public function skills(): array
    {
        $skills = array_map('basename', glob($this->source.'/*', GLOB_ONLYDIR) ?: []);
        sort($skills);

        return $skills;
    }

    public function skillPath(string $skill): string
    {
        return $this->source.'/'.$skill;
    }

    /**
     * @return list<string>
     */
    public function publish(string $destination): array
    {
        $skills = $this->skills();

        foreach ($skills as $skill) {
            $this->filesystem->copyDirectory($this->skillPath($skill), $destination.'/'.$skill);
        }

        return $skills;
    }
}
