<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'declare_strict_types' => true,
    'no_leading_import_slash' => true,
    'no_whitespace_in_blank_line' => true,
    'ordered_class_elements' => [
        'order' => [
            'use_trait',
        ],
    ],
    'ordered_imports' => [
        'imports_order' => [
            'class',
            'function',
            'const',
        ],
        'sort_algorithm' => 'alpha',
    ],
    'phpdoc_line_span' => ['property' => 'single'],
    'return_type_declaration' => ['space_before' => 'none'],
    'simplified_null_return' => true,
    'concat_space' => ['spacing' => 'none'],
    'binary_operator_spaces' => ['default' => 'single_space']
])
//->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
->setRiskyAllowed(true)
->setFinder($finder);
