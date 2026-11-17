<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Ai\AgentGuidelines;
use Aluisio\PhpQualityPack\Ai\McpRegistrar;
use Aluisio\PhpQualityPack\Ai\SkillPublisher;
use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;

final class AiCommand extends ToolCommand
{
    public function name(): string
    {
        return 'ai';
    }

    public function description(): string
    {
        return 'Publish agent skills, register the pqp MCP server and update AGENTS.md / CLAUDE.md.';
    }

    public function options(): array
    {
        return [
            '--skills-path=<dir>' => 'Where to publish skills. Default: .claude/skills.',
            '--mcp=<clients>' => 'Comma-separated MCP clients: '.implode(', ', McpRegistrar::clients()).'. Default: claude.',
            '--no-skills' => 'Skip publishing skills.',
            '--no-mcp' => 'Skip MCP registration.',
            '--no-guidelines' => 'Skip updating AGENTS.md and CLAUDE.md.',
        ];
    }

    public function handle(Input $input, Output $output): int
    {
        $project = $this->project();

        if (! $input->hasOption('no-skills')) {
            $path = $input->option('skills-path') ?? SkillPublisher::DEFAULT_PATH;
            $skills = (new SkillPublisher)->publish($project->path($path));
            $output->success(sprintf('Published skills to %s: %s.', $path, implode(', ', $skills)));
        }

        if (! $input->hasOption('no-mcp')) {
            $registrar = new McpRegistrar;

            foreach (explode(',', $input->option('mcp') ?? 'claude') as $client) {
                $file = $registrar->register($project->root(), trim($client));
                $output->success(sprintf('Registered the "%s" MCP server in %s.', McpRegistrar::SERVER_NAME, $file));
            }
        }

        if (! $input->hasOption('no-guidelines')) {
            foreach ((new AgentGuidelines)->update($project->root()) as $file) {
                $output->success('Updated '.$file.'.');
            }
        }

        return 0;
    }
}
