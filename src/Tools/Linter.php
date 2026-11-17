<?php

declare(strict_types=1);

namespace Aluisio\PhpQualityPack\Tools;

interface Linter
{
    public function name(): string;

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $extra
     */
    public function step(array $paths, bool $check, array $extra = []): Step;
}
