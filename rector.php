<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php86\Rector\Class_\ConstructorReadonlyAssignToDefaultRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        earlyReturn: true
    )
    ->withPhpSets(php86: true)
    ->withSkip([
        ConstructorReadonlyAssignToDefaultRector::class => [
            __DIR__.'/app/Http/Middleware/ReplaceCdnUrl.php',
        ],
    ]);
