<?php

declare(strict_types=1);

use Kenny1911\ClassVisibilityFixer\ClassVisibilityFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$config = (new Config())
    ->registerCustomFixers([
        new ClassVisibilityFixer(),
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__ . '/config')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->append([
                __FILE__,
            ]),
    )
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__ . '/var/' . basename(__FILE__) . '.cache');

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'yoda_style' => true,
    // 'Kenny1911/class_visibility' => true, // PHPStan does not support @psalm-internal annotation
    'numeric_literal_separator' => false,
    'php_unit_data_provider_name' => [
        'prefix' => 'data',
        'suffix' => '',
    ],
    'new_expression_parentheses' => false,
]);

return $config;
