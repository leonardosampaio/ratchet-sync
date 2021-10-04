<?php
namespace RatchetSync;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RatchetSync\Persistence;

class Controller implements MessageComponentInterface {
    protected $clients;
    private $persistence;

    public function __construct($config) {

        if (isset($config->timezone))
        {
            date_default_timezone_set($config->timezone);
        }

        Logger::getInstance()->setOutputFile(__DIR__."/../logs/".date('YmdHis')."_".$config->server->port."_websocket.log");

        $this->persistence = new Persistence($config->mysql);
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        Logger::getInstance()->log("New connection ({$conn->resourceId})");
        Logger::getInstance()->log(json_encode($conn->httpRequest->getHeaders()));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $t = microtime(true);
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new \DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
        
        $this->persistence->saveDataUpDto($msg, $d->format("Y-m-d H:i:s.u"));
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        Logger::getInstance()->log("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        Logger::getInstance()->log("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }
}