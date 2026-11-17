<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read [%s].', $path));
        }

        return $contents;
    }

    public function write(string $path, string $contents): void
    {
        $this->ensureDirectory(dirname($path));

        if (@file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Unable to write [%s].', $path));
        }
    }

    public function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException(sprintf('Unable to create directory [%s].', $path));
        }
    }

    public function copyDirectory(string $source, string $destination): void
    {
        foreach ($this->files($source) as $file) {
            $relativePath = substr($file, strlen(rtrim($source, '/\\')) + 1);

            $this->write($destination.DIRECTORY_SEPARATOR.$relativePath, $this->read($file));
        }
    }

    /**
     * @param  list<string>  $excludedDirectories
     * @return list<string>
     */
    public function files(string $directory, string $extension = '', array $excludedDirectories = [], int $limit = PHP_INT_MAX): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($extension !== '' && $file->getExtension() !== $extension) {
                continue;
            }

            if ($this->isExcluded($file->getPathname(), $excludedDirectories)) {
                continue;
            }

            $files[] = $file->getPathname();

            if (count($files) >= $limit) {
                break;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param  list<string>  $excludedDirectories
     */
    private function isExcluded(string $path, array $excludedDirectories): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        foreach ($excludedDirectories as $excludedDirectory) {
            if (strpos($normalizedPath, '/'.trim($excludedDirectory, '/').'/') !== false) {
                return true;
            }
        }

        return false;
    }
}
