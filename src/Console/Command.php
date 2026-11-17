<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Console;

interface Command
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, string>
     */
    public function options(): array;

    public function handle(Input $input, Output $output): int;
}
