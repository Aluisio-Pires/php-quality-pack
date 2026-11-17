<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Project\Settings;

final class Toolchain
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function project(): Project
    {
        return $this->project;
    }

    public function linter(): Linter
    {
        if ($this->project->hasBinary('pint')) {
            return new Pint($this->project);
        }

        if ($this->project->hasBinary('php-cs-fixer')) {
            return new PhpCsFixer($this->project);
        }

        throw MissingTool::named('Pint', $this->project->runtimePhp()->isAtLeast('8.0') ? 'laravel/pint' : 'friendsofphp/php-cs-fixer');
    }

    public function phpStan(): PhpStan
    {
        $this->ensure('phpstan', 'PHPStan', 'phpstan/phpstan');

        return new PhpStan($this->project);
    }

    public function rector(): Rector
    {
        $this->ensure('rector', 'Rector', 'rector/rector');

        return new Rector($this->project);
    }

    public function pest(): Pest
    {
        $this->ensure('pest', 'Pest', 'pestphp/pest');

        return new Pest($this->project);
    }

    public function has(string $step): bool
    {
        $binaries = [
            'lint' => ['pint', 'php-cs-fixer'],
            'refactor' => ['rector'],
            'analyse' => ['phpstan'],
            'test' => ['pest'],
            'type-coverage' => ['pest'],
        ];

        foreach ($binaries[$step] ?? [] as $binary) {
            if ($this->project->hasBinary($binary)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Step>
     */
    public function checkSteps(bool $machine): array
    {
        $steps = [];

        foreach ($this->enabledSteps() as $name) {
            $step = $this->checkStep($name, $machine);

            if ($step instanceof Step) {
                $steps[] = $step;
            }
        }

        return $steps;
    }

    /**
     * @return list<string>
     */
    public function enabledSteps(): array
    {
        $configured = $this->project->settings()->steps();

        if ($configured !== null) {
            return $configured;
        }

        $tracksTypeCoverage = $this->project->settings()->typeCoverage() !== null;

        return array_values(array_filter(
            Settings::STEPS,
            fn (string $step): bool => $this->has($step) && ($step !== 'type-coverage' || $tracksTypeCoverage),
        ));
    }

    private function checkStep(string $name, bool $machine): ?Step
    {
        switch ($name) {
            case 'lint':
                return $this->linter()->step([], true);
            case 'refactor':
                return $this->rector()->step([], true, $machine);
            case 'analyse':
                return $this->phpStan()->step([], null, $machine);
            case 'test':
                return $this->pest()->step([], $machine);
            case 'type-coverage':
                return $this->typeCoverageStep($machine);
            default:
                return null;
        }
    }

    private function typeCoverageStep(bool $machine): ?Step
    {
        $minimum = $this->project->settings()->typeCoverage();
        $pest = $this->pest();

        if ($minimum === null || ! $pest->supportsTypeCoverage()) {
            return null;
        }

        return $pest->typeCoverageStep($minimum, $machine);
    }

    private function ensure(string $binary, string $tool, string $package): void
    {
        if (! $this->project->hasBinary($binary)) {
            throw MissingTool::named($tool, $package);
        }
    }
}
