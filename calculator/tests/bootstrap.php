<?php

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . str_replace(['Calculator\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
