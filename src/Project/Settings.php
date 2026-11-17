<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Support\Json;

final class Settings
{
    public const FILE = 'pqp.json';

    public const STEPS = ['lint', 'refactor', 'analyse', 'test', 'type-coverage'];

    private ?string $php;

    private ?string $framework;

    /**
     * @var list<string>|null
     */
    private ?array $steps;

    private ?float $typeCoverage;

    /**
     * @param  list<string>|null  $steps
     */
    public function __construct(?string $php = null, ?string $framework = null, ?array $steps = null, ?float $typeCoverage = null)
    {
        $this->php = $php;
        $this->framework = $framework;
        $this->steps = $steps;
        $this->typeCoverage = $typeCoverage;
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            return new self;
        }

        $data = Json::decode((string) file_get_contents($path));
        $steps = is_array($data['steps'] ?? null) ? array_values(array_intersect(self::STEPS, array_filter($data['steps'], 'is_string'))) : null;

        return new self(
            is_string($data['php'] ?? null) ? $data['php'] : null,
            is_string($data['framework'] ?? null) ? $data['framework'] : null,
            $steps,
            is_numeric($data['typeCoverage'] ?? null) ? (float) $data['typeCoverage'] : null,
        );
    }

    public function php(): ?string
    {
        return $this->php;
    }

    public function framework(): ?string
    {
        return $this->framework;
    }

    /**
     * @return list<string>|null
     */
    public function steps(): ?array
    {
        return $this->steps;
    }

    public function typeCoverage(): ?float
    {
        return $this->typeCoverage;
    }

    /**
     * @param  list<string>|null  $steps
     */
    public function withSteps(?array $steps): self
    {
        return new self($this->php, $this->framework, $steps === null ? null : array_values(array_intersect(self::STEPS, $steps)), $this->typeCoverage);
    }

    public function withTypeCoverage(?float $typeCoverage): self
    {
        return new self($this->php, $this->framework, $this->steps, $typeCoverage);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'php' => $this->php,
            'framework' => $this->framework,
            'steps' => $this->steps,
            'typeCoverage' => $this->typeCoverage,
        ], static fn ($value): bool => $value !== null);
    }

    public function toJson(): string
    {
        return Json::pretty((object) $this->toArray());
    }
}
