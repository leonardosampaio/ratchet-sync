<?php
namespace RatchetSync;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RatchetSync\Persistence;

class Controller implements MessageComponentInterface {
    protected $clients;
    private $persistence;

    public function __construct() {
        Logger::getInstance()->setOutputFile(__DIR__.'/../logs/websocket.log');
        $this->persistence = new Persistence();
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        Logger::getInstance()->log("New connection ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $this->persistence->saveDataUpDto($msg);
        Logger::getInstance()->log(sprintf('Connection %d sent message "%s"'
            , $from->resourceId, $msg));
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