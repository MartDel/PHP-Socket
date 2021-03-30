<?php

require __DIR__.'/vendor/autoload.php';

// Include Ratchet
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Define the app port
define('APP_PORT', 8080);

class ServerImpl implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * A new connection is on
     * @param ConnectionInterface $conn Connection data
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection : {$conn->resourceId}\n";
    }

    /**
     * A message is received
     * @param ConnectionInterface $conn Sender connection data
     * @param String $msg The received message
     */
    public function onMessage(ConnectionInterface $conn, $msg) {
        echo sprintf("New message from '%s': %s\n", $conn->resourceId, $msg);
        // Send the received message to all connected clients
        foreach ($this->clients as $client) {
            $message = json_decode($msg, true);
            if ($conn !== $client) {
                $client->send($msg);
            }
        }
    }

    /**
     * A connection is over
     * @param ConnectionInterface $conn Connection data
     */
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection to {$conn->resourceId} is over.\n";
    }

    /**
     * An error is occured
     * @param ConnectionInterface $conn Involved connection data
     * @param Exception $e Error data
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occured on connection {$conn->resourceId}: {$e->getMessage()}\n\n";
        $conn->close();
    }
}

// Create and run the server
$server = IoServer::factory(new HttpServer(new WsServer(new ServerImpl())), APP_PORT);
echo "Server is running on port " . APP_PORT . "...\n";
$server->run();
