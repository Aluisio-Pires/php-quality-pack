<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/bin',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/tests/Fixtures',
    ])
    ->withPhpSets()
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
    ]);
