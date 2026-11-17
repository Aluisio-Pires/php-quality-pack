<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Setup\Generators;

use Aluisio\PhpQualityPack\Project\Conventions;
use Aluisio\PhpQualityPack\Project\Project;
use Aluisio\PhpQualityPack\Support\Json;

final class PintConfig
{
    public const FILE = 'pint.json';

    private LintRules $rules;

    public function __construct(?LintRules $rules = null)
    {
        $this->rules = $rules ?? new LintRules;
    }

    public function render(Project $project, Conventions $conventions): string
    {
        return Json::pretty([
            'preset' => $project->framework()->pintPreset(),
            'rules' => $this->rules->rules($project->targetPhp(), $conventions),
        ]);
    }
}
