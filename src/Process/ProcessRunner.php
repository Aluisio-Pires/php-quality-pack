<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Process;

use RuntimeException;

final class ProcessRunner implements Runner
{
    public function run(array $command, string $workingDirectory): ProcessResult
    {
        $outputFile = $this->temporaryFile();
        $errorFile = $this->temporaryFile();

        $exitCode = $this->execute($command, $workingDirectory, [
            0 => ['pipe', 'r'],
            1 => ['file', $outputFile, 'w'],
            2 => ['file', $errorFile, 'w'],
        ], $this->environment());

        $result = new ProcessResult(
            $command,
            $exitCode,
            (string) file_get_contents($outputFile),
            (string) file_get_contents($errorFile),
        );

        unlink($outputFile);
        unlink($errorFile);

        return $result;
    }

    public function stream(array $command, string $workingDirectory): int
    {
        return $this->execute($command, $workingDirectory, [STDIN, STDOUT, STDERR], null);
    }

    /**
     * @param  list<string>  $command
     * @param  array<int, list<string>|resource>  $descriptors
     * @param  array<string, string>|null  $environment
     */
    private function execute(array $command, string $workingDirectory, array $descriptors, ?array $environment): int
    {
        $process = proc_open($command, $descriptors, $pipes, $workingDirectory, $environment);

        if (! is_resource($process)) {
            throw new RuntimeException(sprintf('Unable to start [%s].', implode(' ', $command)));
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        return proc_close($process);
    }

    /**
     * @return array<string, string>
     */
    private function environment(): array
    {
        $environment = [];

        foreach (getenv() as $name => $value) {
            if (! AgentEnvironment::isAgentVariable($name)) {
                $environment[$name] = $value;
            }
        }

        return array_merge($environment, AgentEnvironment::OVERRIDES);
    }

    private function temporaryFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pqp');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary file.');
        }

        return $path;
    }
}
