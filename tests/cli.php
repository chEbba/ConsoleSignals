<?php

include __DIR__ . '/bootstrap.php';

$cli = new \Symfony\Component\Console\Application();
$cli->add(new \Che\ConsoleSignals\Tests\CommandStub());

$cli->run();
