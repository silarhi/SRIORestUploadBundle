<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->append([
        __FILE__,
        __DIR__.'/rector.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => ['import_classes' => true],
    ])
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache')
    ->setFinder($finder)
;
