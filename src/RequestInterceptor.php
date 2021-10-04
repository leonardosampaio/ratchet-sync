<?php
namespace RatchetSync;
use \Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Psr\Http\Message\RequestInterface;

class RequestInterceptor implements HttpServerInterface {
    private $delegate;
    public function __construct(HttpServerInterface $delegate) {
        $this->delegate = $delegate;
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null) {
        Logger::getInstance()->log("Request: " . json_encode($request));
        $this->delegate->onOpen($conn, $request);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {}
    public function onClose(ConnectionInterface $conn) {}
    public function onMessage(ConnectionInterface $from, $msg) {}
}