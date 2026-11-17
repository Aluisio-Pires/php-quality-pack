<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Application;
use Aluisio\PhpQualityPack\Mcp\Server;
use Aluisio\PhpQualityPack\Process\ProcessResult;
use Aluisio\PhpQualityPack\Support\Json;
use Tests\Support\FakeRunner;

function mcpServer(?FakeRunner $runner = null): Server
{
    return new Server(new Application(fixturePath('laravel'), $runner ?? new FakeRunner));
}

function rpc(Server $server, string $method, array $params = []): array
{
    return Json::decode((string) $server->respond(Json::encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params])));
}

it('negotiates the protocol version', function (): void {
    $response = rpc(mcpServer(), 'initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'test', 'version' => '1']]);

    expect($response['result']['protocolVersion'])->toBe('2025-06-18')
        ->and($response['result']['serverInfo']['name'])->toBe('pqp')
        ->and($response['result']['capabilities'])->toHaveKeys(['tools', 'prompts', 'resources']);
});

it('falls back to the latest protocol version', function (): void {
    $response = rpc(mcpServer(), 'initialize', ['protocolVersion' => '1999-01-01']);

    expect($response['result']['protocolVersion'])->toBe(Server::PROTOCOL_VERSIONS[0]);
});

it('ignores notifications', function (): void {
    expect(mcpServer()->respond('{"jsonrpc":"2.0","method":"notifications/initialized"}'))->toBeNull();
});

it('encodes empty objects as JSON objects', function (): void {
    expect(mcpServer()->respond('{"jsonrpc":"2.0","id":7,"method":"ping"}'))->toBe('{"jsonrpc":"2.0","id":7,"result":{}}')
        ->and(mcpServer()->respond('{"jsonrpc":"2.0","id":1,"method":"tools/list"}'))->toContain('"properties":{}');
});

it('lists the quality tools', function (): void {
    $tools = array_column(rpc(mcpServer(), 'tools/list')['result']['tools'], 'name');

    expect($tools)->toBe(['detect', 'check', 'lint', 'refactor', 'analyse', 'test', 'fix', 'upgrade']);
});

it('detects the project through a tool call', function (): void {
    $response = rpc(mcpServer(), 'tools/call', ['name' => 'detect', 'arguments' => []]);
    $profile = Json::decode($response['result']['content'][0]['text']);

    expect($response['result']['isError'])->toBeFalse()
        ->and($profile['framework'])->toBe('laravel')
        ->and($profile['targetPhp'])->toBe('8.2');
});

it('runs PHPStan and returns compact errors', function (): void {
    $runner = new FakeRunner(static fn (array $command): ProcessResult => new ProcessResult($command, 1, Json::encode([
        'totals' => ['errors' => 0, 'file_errors' => 1],
        'files' => [fixturePath('laravel').'/app/Models/User.php' => ['messages' => [['message' => 'Loose comparison.', 'line' => 9]]]],
        'errors' => [],
    ])));

    $response = rpc(mcpServer($runner), 'tools/call', ['name' => 'analyse', 'arguments' => ['paths' => ['app'], 'level' => 8]]);
    $report = Json::decode($response['result']['content'][0]['text']);

    expect($runner->commands[0])->toContain('--level=8')
        ->and($runner->commands[0])->toContain('app')
        ->and($report['passed'])->toBeFalse()
        ->and($report['steps'][0]['errors'][0])->toBe(['file' => 'app/Models/User.php', 'line' => 9, 'message' => 'Loose comparison.']);
});

it('runs Rector as a dry run unless apply is true', function (): void {
    $runner = new FakeRunner;

    rpc(mcpServer($runner), 'tools/call', ['name' => 'refactor', 'arguments' => []]);
    rpc(mcpServer($runner), 'tools/call', ['name' => 'refactor', 'arguments' => ['apply' => true]]);

    expect($runner->commands[0])->toContain('--dry-run')
        ->and($runner->commands[1])->not->toContain('--dry-run');
});

it('forwards the test filter to Pest', function (): void {
    $runner = new FakeRunner;

    rpc(mcpServer($runner), 'tools/call', ['name' => 'test', 'arguments' => ['filter' => 'admin']]);

    expect($runner->commands[0])->toContain('--filter=admin');
});

it('reports tool errors', function (): void {
    $response = rpc(mcpServer(), 'tools/call', ['name' => 'upgrade', 'arguments' => []]);

    expect($response['result']['isError'])->toBeTrue()
        ->and($response['result']['content'][0]['text'])->toContain('--php');
});

it('serves prompts and skill resources', function (): void {
    $server = mcpServer();
    $prompt = rpc($server, 'prompts/get', ['name' => 'fix-types', 'arguments' => ['path' => 'app/Models']]);
    $resources = rpc($server, 'resources/list')['result']['resources'];
    $skill = rpc($server, 'resources/read', ['uri' => 'pqp://skills/pqp']);

    expect($prompt['result']['messages'][0]['content']['text'])->toContain('app/Models')
        ->and(array_column($resources, 'uri'))->toContain('pqp://skills/pqp')
        ->and($skill['result']['contents'][0]['text'])->toContain('name: pqp');
});

it('returns JSON-RPC errors', function (): void {
    $server = mcpServer();

    expect(rpc($server, 'unknown/method')['error']['code'])->toBe(-32601)
        ->and(rpc($server, 'tools/call', ['name' => 'nope'])['error']['code'])->toBe(-32602)
        ->and(Json::decode((string) $server->respond('not json'))['error']['code'])->toBe(-32700);
});

it('listens on streams', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    fwrite($input, "{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"ping\"}\n\n{\"jsonrpc\":\"2.0\",\"method\":\"notifications/initialized\"}\n");
    rewind($input);

    mcpServer()->listen($input, $output);
    rewind($output);

    expect(stream_get_contents($output))->toBe("{\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{}}\n");
});
