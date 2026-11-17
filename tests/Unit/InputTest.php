<?php

declare(strict_types=1);

use Aluisio\PhpQualityPack\Console\Input;

it('parses command, arguments, options and passthrough', function (): void {
    $input = Input::fromArgv(['pqp', 'analyse', 'src', '--level=5', '--json', '--', '--memory-limit=2G']);

    expect($input->command())->toBe('analyse')
        ->and($input->arguments())->toBe(['src'])
        ->and($input->option('level'))->toBe('5')
        ->and($input->hasOption('json'))->toBeTrue()
        ->and($input->option('json'))->toBeNull()
        ->and($input->passthrough())->toBe(['--memory-limit=2G']);
});

it('forwards unknown options to the tool', function (): void {
    $input = Input::fromArgv(['pqp', 'test', '--filter=Money', '--json', '-v', '--', '--compact']);

    expect($input->forwarded(['json']))->toBe(['--filter=Money', '-v', '--compact']);
});
