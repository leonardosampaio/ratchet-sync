<?php

namespace RatchetSync;
use RatchetSync\Logger;
use PDO;

class Persistence {
    
    private $pdo;

    public function __construct() {
        $this->getConnection();
    }

    public function getConnection()
    {
        $configurationPath = __DIR__.'/../configuration/configuration.json';
        if (!file_exists($configurationPath))
        {
            die('Configuration file '.
                __DIR__.'/../configuration/configuration.json not found');
        }
        $config = json_decode(file_get_contents($configurationPath))->mysql;
        
        $dsn = "mysql:host=$config->host;port=$config->port;dbname=$config->database;charset=$config->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
             $this->pdo = new PDO($dsn, $config->user, $config->password, $options);
        } catch (\Exception $e) {
            Logger::getInstance()->log('PDO error: ' . json_encode($e));
        }
    }

    public function saveDataUpDto($msg)
    {
        if (!$this->pdo)
        {
            //try again in 5 seconds
            sleep(5);
            $this->getConnection();

            if (!$this->pdo)
            {
                Logger::getInstance()->log(
                    '[Persistence::saveDataUpDto] Invalid PDO instance obtained, ignoring insert of ' . 
                    json_encode($msg));
                return false;
            }
        }

        $sql = "INSERT INTO TODO_TABLE VALUES TODO_COLUMNS=?";
        $stmt= $this->pdo->prepare($sql);
        return $stmt->execute([$msg]);
    }
} 
