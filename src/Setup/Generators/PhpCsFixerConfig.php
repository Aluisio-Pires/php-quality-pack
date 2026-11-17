<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

use Aluisio\PhpQualityPack\Project\Conventions;
use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Support\PhpArray;

final class PhpCsFixerConfig
{
    public const FILE = '.php-cs-fixer.dist.php';

    private const EXCLUDED_DIRECTORIES = ['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'var'];

    private LintRules $rules;

    public function __construct(?LintRules $rules = null)
    {
        $this->rules = $rules ?? new LintRules;
    }

    public function render(Project $project, Conventions $conventions): string
    {
        $rules = array_merge(
            [$project->framework()->phpCsFixerPreset() => true],
            $this->rules->rules($project->targetPhp(), $conventions),
        );

        return implode("\n", [
            '<?php',
            '',
            '$finder = PhpCsFixer\Finder::create()',
            '    ->in(__DIR__)',
            '    ->exclude('.PhpArray::export(self::EXCLUDED_DIRECTORIES, 1).');',
            '',
            'return (new PhpCsFixer\Config())',
            '    ->setRiskyAllowed(true)',
            '    ->setRules('.PhpArray::export($rules, 1).')',
            '    ->setFinder($finder);',
            '',
        ]);
    }
}
