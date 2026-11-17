<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup;

use Aluisio\PhpQualityPack\Process\Runner;

final class Composer
{
    private const PLUGINS = ['pestphp/pest-plugin'];

    private Runner $runner;

    private string $root;

    public function __construct(Runner $runner, string $root)
    {
        $this->runner = $runner;
        $this->root = $root;
    }

    public function allowPlugins(): void
    {
        foreach (self::PLUGINS as $plugin) {
            $this->runner->run(array_merge($this->binary(), ['config', '--no-interaction', 'allow-plugins.'.$plugin, 'true']), $this->root);
        }
    }

    /**
     * @param  list<Requirement>  $requirements
     */
    public function require(array $requirements): bool
    {
        if ($requirements === []) {
            return true;
        }

        $command = array_merge($this->binary(), ['require', '--dev', '--with-all-dependencies', '--no-interaction']);

        foreach ($requirements as $requirement) {
            $command[] = $requirement->toComposerArgument();
        }

        return $this->runner->stream($command, $this->root) === 0;
    }

    /**
     * @return list<string>
     */
    private function binary(): array
    {
        $configured = getenv('PQP_COMPOSER');

        if (is_string($configured) && $configured !== '') {
            return preg_match('/\.phar$/', $configured) === 1 ? [PHP_BINARY, $configured] : [$configured];
        }

        if (is_file($this->root.'/composer.phar')) {
            return [PHP_BINARY, $this->root.'/composer.phar'];
        }

        return ['composer'];
    }
}
