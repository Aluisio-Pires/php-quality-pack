<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Mcp;

use Aluisio\PhpQualityPack\Ai\SkillPublisher;
use InvalidArgumentException;

final class ResourceCatalog
{
    private const PREFIX = 'pqp://skills/';

    private SkillPublisher $skills;

    public function __construct(?SkillPublisher $skills = null)
    {
        $this->skills = $skills ?? new SkillPublisher;
    }

    /**
     * @return list<array<string, string>>
     */
    public function definitions(): array
    {
        return array_map(fn (string $skill): array => [
            'uri' => self::PREFIX.$skill,
            'name' => $skill,
            'title' => 'Skill: '.$skill,
            'description' => $this->description($skill),
            'mimeType' => 'text/markdown',
        ], $this->skills->skills());
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $uri): array
    {
        $skill = strpos($uri, self::PREFIX) === 0 ? substr($uri, strlen(self::PREFIX)) : '';

        if (! in_array($skill, $this->skills->skills(), true)) {
            throw new InvalidArgumentException(sprintf('Unknown resource [%s].', $uri));
        }

        return [
            'contents' => [
                ['uri' => $uri, 'mimeType' => 'text/markdown', 'text' => $this->contents($skill)],
            ],
        ];
    }

    private function description(string $skill): string
    {
        if (preg_match('/^description:\s*(.+)$/m', $this->contents($skill), $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    private function contents(string $skill): string
    {
        return (string) file_get_contents($this->skills->skillPath($skill).'/SKILL.md');
    }
}
