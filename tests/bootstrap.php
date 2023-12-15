<?php

if (!is_file($loaderFile = __DIR__.'/../vendor/autoload.php')) {
    throw new LogicException('Could not find autoload.php in vendor/. Did you run "composer install --dev"?');
}

require_once $loaderFile;

$testEnv = $_SERVER['TEST_FILESYSTEM'] ?? 'gaufrette';
$message = sprintf('Currently testing filesystem layer: "%s" (options: Gaufrette, Flysystem. see \'test.sh\' script)', $testEnv);
if (PHP_SAPI === 'cli') {
    echo "\e[48;5;202m{$message}\e[49m\r\n\r\n";
} else {
    echo $message;
}
