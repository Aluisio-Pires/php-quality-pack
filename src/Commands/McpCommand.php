<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Commands;

use Aluisio\PhpQualityPack\Application;
use Aluisio\PhpQualityPack\Console\Command;
use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Mcp\Server;

final class McpCommand implements Command
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function name(): string
    {
        return 'mcp';
    }

    public function description(): string
    {
        return 'Start the pqp MCP server over stdio.';
    }

    public function options(): array
    {
        return [];
    }

    public function handle(Input $input, Output $output): int
    {
        (new Server($this->application))->listen(STDIN, STDOUT);

        return 0;
    }
}
