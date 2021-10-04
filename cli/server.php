<?php
use RatchetSync\Controller;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!isset($argv[1]) || empty($argv[1]))
{
    die('Inform path to configuration file as argument');
}

$configurationPath = $argv[1];
if (!file_exists($configurationPath))
{
    die("Configuration file $configurationPath not found");
}
$config = null;

try {
    $config = json_decode(file_get_contents($configurationPath), false, 512, JSON_THROW_ON_ERROR);
}
catch (\Exception $e)
{
    die('Invalid JSON file: ' . $configurationPath);
}

$port = isset($config) && isset($config->server) && isset($config->server->port) ?
    $config->server->port : null;

if (!$port || !is_int($port))
{
    die('Invalid server port in configuration file');
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Controller($config)
        )
    ),
    $port
);

$server->run();