<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Ai;

use Aluisio\PhpQualityPack\Support\Filesystem;
use InvalidArgumentException;
use stdClass;

final class McpRegistrar
{
    public const SERVER_NAME = 'pqp';

    private const CLIENTS = [
        'claude' => ['file' => '.mcp.json', 'key' => 'mcpServers', 'type' => false],
        'cursor' => ['file' => '.cursor/mcp.json', 'key' => 'mcpServers', 'type' => false],
        'vscode' => ['file' => '.vscode/mcp.json', 'key' => 'servers', 'type' => true],
    ];

    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * @return list<string>
     */
    public static function clients(): array
    {
        return array_keys(self::CLIENTS);
    }

    public function register(string $root, string $client, string $binary = 'vendor/bin/pqp'): string
    {
        if (! isset(self::CLIENTS[$client])) {
            throw new InvalidArgumentException(sprintf('Unknown MCP client [%s]. Use one of: %s.', $client, implode(', ', self::clients())));
        }

        $definition = self::CLIENTS[$client];
        $path = $root.'/'.$definition['file'];
        $config = $this->load($path);
        $key = $definition['key'];

        $servers = isset($config->{$key}) && $config->{$key} instanceof stdClass ? $config->{$key} : new stdClass;
        $server = new stdClass;

        if ($definition['type']) {
            $server->type = 'stdio';
        }

        $server->command = 'php';
        $server->args = [$binary, 'mcp'];

        $servers->{self::SERVER_NAME} = $server;
        $config->{$key} = $servers;

        $this->filesystem->write($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return $definition['file'];
    }

    private function load(string $path): stdClass
    {
        if (! $this->filesystem->exists($path)) {
            return new stdClass;
        }

        $config = json_decode($this->filesystem->read($path));

        return $config instanceof stdClass ? $config : new stdClass;
    }
}
