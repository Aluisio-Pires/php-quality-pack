<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Project;

use Aluisio\PhpQualityPack\Support\Filesystem;

final class ConventionScanner
{
    private const FILE_LIMIT = 2000;

    private const IGNORED_TOKENS = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * @param  list<string>  $directories
     */
    public function scan(string $root, array $directories): Conventions
    {
        $files = 0;
        $strictTypesFiles = 0;
        $classes = 0;
        $finalClasses = 0;
        $looseComparisons = 0;

        foreach ($this->phpFiles($root, $directories) as $file) {
            $code = $this->filesystem->read($file);
            $tokens = token_get_all($code);

            $files++;
            $strictTypesFiles += preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/i', $code) === 1 ? 1 : 0;

            foreach ($tokens as $index => $token) {
                if (! is_array($token)) {
                    continue;
                }

                if ($token[0] === T_IS_EQUAL || $token[0] === T_IS_NOT_EQUAL) {
                    $looseComparisons++;
                }

                if ($token[0] !== T_CLASS) {
                    continue;
                }

                $modifiers = $this->modifiersBefore($tokens, $index);

                if ($modifiers === null || in_array(T_ABSTRACT, $modifiers, true)) {
                    continue;
                }

                $classes++;
                $finalClasses += in_array(T_FINAL, $modifiers, true) ? 1 : 0;
            }
        }

        return new Conventions($files, $strictTypesFiles, $classes, $finalClasses, $looseComparisons);
    }

    /**
     * @param  list<string>  $directories
     * @return list<string>
     */
    private function phpFiles(string $root, array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $remaining = self::FILE_LIMIT - count($files);

            if ($remaining <= 0) {
                break;
            }

            $files = array_merge($files, $this->filesystem->files(
                $root.'/'.$directory,
                'php',
                ['vendor', 'node_modules', 'storage', 'cache', 'var'],
                $remaining,
            ));
        }

        return array_values(array_unique($files));
    }

    /**
     * @param  array<int, mixed>  $tokens
     * @return list<int>|null
     */
    private function modifiersBefore(array $tokens, int $index): ?array
    {
        $modifiers = [];
        $classModifiers = array_filter([T_FINAL, T_ABSTRACT, defined('T_READONLY') ? constant('T_READONLY') : null]);

        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];

            if (is_array($token) && in_array($token[0], self::IGNORED_TOKENS, true)) {
                continue;
            }

            if (is_array($token) && in_array($token[0], $classModifiers, true)) {
                $modifiers[] = $token[0];

                continue;
            }

            if (is_array($token) && in_array($token[0], [T_NEW, T_DOUBLE_COLON], true)) {
                return null;
            }

            break;
        }

        return $modifiers;
    }
}
