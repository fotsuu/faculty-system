<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new \Symfony\Component\Console\Input\ArrayInput([
        'command' => 'tinker',
    ]),
    $output = new \Symfony\Component\Console\Output\BufferedOutput()
);

echo $output->fetch();
