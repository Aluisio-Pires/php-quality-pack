<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Project\Profile;
use Aluisio\PhpQualityPack\Support\Json;

final class DetectCommand extends ToolCommand
{
    public function name(): string
    {
        return 'detect';
    }

    public function description(): string
    {
        return 'Show the detected project profile: PHP versions, framework, paths, tools and conventions.';
    }

    public function options(): array
    {
        return [
            '--json' => 'Print the profile as JSON.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $profile = (new Profile)->build($this->project());

        if ($this->machine($input)) {
            $output->line(rtrim(Json::pretty($profile)));

            return 0;
        }

        $output->title('Project');
        $output->definitions([
            'Root' => $profile['root'],
            'Framework' => trim($profile['framework'].' '.$profile['frameworkVersion']),
            'Target PHP' => $profile['targetPhp'],
            'Runtime PHP' => $profile['runtimePhp'],
            'Analysis paths' => implode(', ', $profile['analysisPaths']),
            'Test paths' => $this->listOrNone($profile['testPaths']),
            'Check steps' => $this->listOrNone($profile['steps']),
        ]);
        $output->line();
        $output->title('Tools');
        $output->definitions(array_map(
            static fn (?string $version): string => $version ?? 'not installed',
            $profile['tools'],
        ));

        return 0;
    }

    /**
     * @param  list<string>  $items
     */
    private function listOrNone(array $items): string
    {
        return $items === [] ? 'none' : implode(', ', $items);
    }
}
