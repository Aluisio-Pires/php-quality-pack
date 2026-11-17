<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Ai\AgentGuidelines;
use Aluisio\PhpQualityPack\Ai\McpRegistrar;
use Aluisio\PhpQualityPack\Ai\SkillPublisher;
use Aluisio\PhpQualityPack\Support\Json;

beforeEach(function (): void {
    $this->directory = temporaryProject('library');
});

afterEach(function (): void {
    removeDirectory($this->directory);
});

it('publishes every skill', function (): void {
    $skills = (new SkillPublisher)->publish($this->directory.'/.claude/skills');

    expect($skills)->toContain('pqp')
        ->and(file_exists($this->directory.'/.claude/skills/pqp/SKILL.md'))->toBeTrue();
});

it('registers the MCP server and keeps other servers', function (): void {
    file_put_contents($this->directory.'/.mcp.json', '{"mcpServers": {"other": {"command": "other"}}}');

    (new McpRegistrar)->register($this->directory, 'claude');
    (new McpRegistrar)->register($this->directory, 'vscode');

    $claude = Json::decode((string) file_get_contents($this->directory.'/.mcp.json'));
    $vscode = Json::decode((string) file_get_contents($this->directory.'/.vscode/mcp.json'));

    expect($claude['mcpServers'])->toHaveKey('other')
        ->and($claude['mcpServers']['pqp'])->toBe(['command' => 'php', 'args' => ['vendor/bin/pqp', 'mcp']])
        ->and($vscode['servers']['pqp']['type'])->toBe('stdio');
});

it('rejects unknown MCP clients', function (): void {
    (new McpRegistrar)->register($this->directory, 'unknown');
})->throws(InvalidArgumentException::class);

it('upserts the guidelines block once', function (): void {
    $guidelines = new AgentGuidelines;
    $once = $guidelines->upsert("# Agents\n");
    $twice = $guidelines->upsert($once);

    expect($twice)->toBe($once)
        ->and(substr_count($twice, '<!-- pqp:start -->'))->toBe(1)
        ->and($twice)->toStartWith("# Agents\n\n<!-- pqp:start -->");
});

it('only updates existing guideline files', function (): void {
    file_put_contents($this->directory.'/CLAUDE.md', "# Claude\n");

    expect((new AgentGuidelines)->update($this->directory))->toBe(['CLAUDE.md'])
        ->and(file_exists($this->directory.'/AGENTS.md'))->toBeFalse();
});
