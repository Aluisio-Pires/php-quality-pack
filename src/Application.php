<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack;

use Aluisio\PhpQualityPack\Commands\AiCommand;
use Aluisio\PhpQualityPack\Commands\AnalyseCommand;
use Aluisio\PhpQualityPack\Commands\CheckCommand;
use Aluisio\PhpQualityPack\Commands\DetectCommand;
use Aluisio\PhpQualityPack\Commands\FixCommand;
use Aluisio\PhpQualityPack\Commands\InstallCommand;
use Aluisio\PhpQualityPack\Commands\LintCommand;
use Aluisio\PhpQualityPack\Commands\McpCommand;
use Aluisio\PhpQualityPack\Commands\RefactorCommand;
use Aluisio\PhpQualityPack\Commands\TestCommand;
use Aluisio\PhpQualityPack\Commands\UpgradeCommand;
use Aluisio\PhpQualityPack\Console\Command;
use Aluisio\PhpQualityPack\Console\Input;
use Aluisio\PhpQualityPack\Console\Output;
use Aluisio\PhpQualityPack\Process\ProcessRunner;
use Aluisio\PhpQualityPack\Process\Runner;
use Aluisio\PhpQualityPack\Project\ProjectDetector;
use InvalidArgumentException;
use RuntimeException;

final class Application
{
    public const VERSION = '1.0.0';

    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    public function __construct(string $directory, ?Runner $runner = null, ?ProjectDetector $detector = null)
    {
        $runner ??= new ProcessRunner;

        $this->register(
            new InstallCommand($runner, $directory, $detector),
            new DetectCommand($runner, $directory, $detector),
            new CheckCommand($runner, $directory, $detector),
            new FixCommand($runner, $directory, $detector),
            new LintCommand($runner, $directory, $detector),
            new RefactorCommand($runner, $directory, $detector),
            new AnalyseCommand($runner, $directory, $detector),
            new TestCommand($runner, $directory, $detector),
            new UpgradeCommand($runner, $directory, $detector),
            new AiCommand($runner, $directory, $detector),
            new McpCommand($this),
        );
    }

    /**
     * @param  list<string>  $argv
     */
    public function run(array $argv, ?Output $output = null): int
    {
        $output ??= Output::stdout();
        $input = Input::fromArgv($argv);

        if ($input->hasOption('version') || $input->hasOption('V')) {
            $output->line('php-quality-pack '.self::VERSION);

            return 0;
        }

        if (in_array($input->command(), ['', 'help', 'list'], true)) {
            $this->help($output, $input->arguments()[0] ?? null);

            return 0;
        }

        if ($input->hasOption('help') || $input->hasOption('h')) {
            $this->help($output, $input->command());

            return 0;
        }

        try {
            return $this->call($input, $output);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $output->error($exception->getMessage());

            return 1;
        }
    }

    public function call(Input $input, Output $output): int
    {
        $command = $this->commands[$input->command()] ?? null;

        if ($command === null) {
            throw new InvalidArgumentException(sprintf('Unknown command [%s]. Run `pqp help` to see the available commands.', $input->command()));
        }

        return $command->handle($input, $output);
    }

    /**
     * @return array<string, Command>
     */
    public function commands(): array
    {
        return $this->commands;
    }

    private function register(Command ...$commands): void
    {
        foreach ($commands as $command) {
            $this->commands[$command->name()] = $command;
        }
    }

    private function help(Output $output, ?string $name): void
    {
        $command = $name === null ? null : $this->commands[$name] ?? null;

        if ($command !== null) {
            $output->title('pqp '.$command->name());
            $output->line('  '.$command->description());
            $output->line();

            if ($command->options() !== []) {
                $output->definitions($command->options());
            }

            return;
        }

        $output->title('php-quality-pack '.self::VERSION);
        $output->line('  Usage: pqp <command> [paths...] [options] [-- tool options]');
        $output->line();
        $output->definitions(array_map(static fn (Command $command): string => $command->description(), $this->commands));
    }
}
