<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

final class Conventions
{
    private int $files;

    private int $strictTypesFiles;

    private int $classes;

    private int $finalClasses;

    private int $looseComparisons;

    public function __construct(int $files = 0, int $strictTypesFiles = 0, int $classes = 0, int $finalClasses = 0, int $looseComparisons = 0)
    {
        $this->files = $files;
        $this->strictTypesFiles = $strictTypesFiles;
        $this->classes = $classes;
        $this->finalClasses = $finalClasses;
        $this->looseComparisons = $looseComparisons;
    }

    public function files(): int
    {
        return $this->files;
    }

    public function usesStrictTypesEverywhere(): bool
    {
        return $this->files > 0 && $this->strictTypesFiles === $this->files;
    }

    public function usesFinalClassesEverywhere(): bool
    {
        return $this->classes > 0 && $this->finalClasses === $this->classes;
    }

    public function avoidsLooseComparisons(): bool
    {
        return $this->files > 0 && $this->looseComparisons === 0;
    }

    /**
     * @return array<string, int|bool>
     */
    public function toArray(): array
    {
        return [
            'files' => $this->files,
            'strictTypesFiles' => $this->strictTypesFiles,
            'classes' => $this->classes,
            'finalClasses' => $this->finalClasses,
            'looseComparisons' => $this->looseComparisons,
            'strictTypesEverywhere' => $this->usesStrictTypesEverywhere(),
            'finalClassesEverywhere' => $this->usesFinalClassesEverywhere(),
            'noLooseComparisons' => $this->avoidsLooseComparisons(),
        ];
    }
}
