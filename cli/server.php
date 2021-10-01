<?php
use Ratchet\Server\IoServer;
use RatchetSync\Controller;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new Controller(),
    8080
);

$server->run();