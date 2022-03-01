<?php

namespace app;

use websocket\Logger;
use PDO;

class Persistence {
    
    private $pdo;
    private $config;
    private $nextTryInSeconds;

    public function __construct($mysqlConfig) {
        $this->nextTryInSeconds = 5;
        $this->config = $mysqlConfig;
        $this->getConnection();
    }

    public function getConnection()
    {
        $dsn =
            "mysql:host=".$this->config->host.";port=".$this->config->port.";dbname=".$this->config->database.";charset=".$this->config->charset;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
             $this->pdo = new PDO($dsn, $this->config->user, $this->config->password, $options);
        } catch (\Exception $e) {
            Logger::getInstance()->log('PDO error: ' . json_encode($e, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Get all unprocessed triggers.
     * 
     * @return array triggers as objects
     */
    public function getDataDownTriggers()
    {
        try
        {
            while(!$this->pdo)
            {
                sleep($this->nextTryInSeconds);
                
                if ($this->nextTryInSeconds <= 60)
                {
                    $this->nextTryInSeconds+=5;
                }

                $this->getConnection();
    
                if (!$this->pdo)
                {
                    Logger::getInstance()->log(
                        '[Persistence::saveDataUpDto] Invalid PDO instance obtained');
                    Logger::getInstance()->log(
                        '[Persistence::saveDataUpDto] Retrying in '.$this->nextTryInSeconds.' seconds');    
                }
            }
    
            $sqlDataUpDto = "SELECT * FROM createDataDownTrigger
                                WHERE processed = false
                                AND due_date < NOW()";
            $stmt= $this->pdo->prepare($sqlDataUpDto);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        catch (\Exception $e)
        {
            Logger::getInstance()->log('Error: ' . (($json = json_encode($e, JSON_PRETTY_PRINT)) ? $json : $e->getMessage()));
            return false;
        }
    }

    /**
     * Updates the current trigger as processed (will be ignored on the next runs).
     * 
     * @param $id
     * @return bool true in case of success
     */
    public function setDataDownTriggerProcessed($id)
    {
        try
        {
            while(!$this->pdo)
            {
                sleep($this->nextTryInSeconds);
                
                if ($this->nextTryInSeconds <= 60)
                {
                    $this->nextTryInSeconds+=5;
                }

                $this->getConnection();
    
                if (!$this->pdo)
                {
                    Logger::getInstance()->log(
                        '[Persistence::saveDataUpDto] Invalid PDO instance obtained');
                    Logger::getInstance()->log(
                        '[Persistence::saveDataUpDto] Retrying in '.$this->nextTryInSeconds.' seconds');    
                }
            }
    
            $sqlDataUpDto = "UPDATE createDataDownTrigger SET processed = TRUE WHERE id = :id";
            $stmt= $this->pdo->prepare($sqlDataUpDto);
            return $stmt->execute(['id' => $id]);
        }
        catch (\Exception $e)
        {
            Logger::getInstance()->log('Error: ' . (($json = json_encode($e, JSON_PRETTY_PRINT)) ? $json : $e->getMessage()));
            return false;
        }
    }
}