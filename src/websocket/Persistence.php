<?php

namespace websocket;

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

    public function saveDataUpDto($msgJsonStr, $receivedIn)
    {
        try {

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
                        '[Persistence::saveDataUpDto] Invalid PDO instance obtained, ignoring insert of ' . 
                        json_encode($msgJsonStr));
                    Logger::getInstance()->log(
                        '[Persistence::saveDataUpDto] Retrying in '.$this->nextTryInSeconds.' seconds');    
                }
            }
    
            $this->pdo->beginTransaction();
    
            $message = json_decode($msgJsonStr, false, 512, JSON_THROW_ON_ERROR);
    
            $sqlDataUpDto = "INSERT INTO ".$this->config->database.".DataUpDto
                    (
                        message_id,
                        endDevice_devEui,
                        endDevice_devAddr,
                        endDevice_cluster_id,
                        fCntDown,
                        fCntUp,
                        adr,
                        confirmed,
                        encrypted,
                        payload,
                        encodingType,
                        recvTime,
                        gwRecvTime,
                        `delayed`,
                        ulFrequency,
                        modulation,
                        dataRate,
                        codingRate,
                        gwCnt,
                        received_in,
                        classB,
                        fPort
                    )
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt= $this->pdo->prepare($sqlDataUpDto);
            $insertedDataUpDto = $stmt->execute(
                [
                    isset($message->id) ? $message->id : null,
                    isset($message->endDevice) ? $message->endDevice->devEui : null,
                    isset($message->endDevice) ? $message->endDevice->devAddr : null,
                    isset($message->endDevice) ? $message->endDevice->cluster->id : null,
                    isset($message->fCntDown) ? $message->fCntDown : null,
                    isset($message->fCntUp) ? $message->fCntUp : null,
                    isset($message->adr) ? ($message->adr === true ? 1 : 0): null,
                    isset($message->confirmed) ? ($message->confirmed === true ? 1 : 0) : null,
                    isset($message->encrypted) ? ($message->encrypted === true ? 1 : 0) : null,
                    isset($message->payload) ? $message->payload : null,
                    isset($message->encodingType) ? $message->encodingType : null,
                    isset($message->recvTime) ? $message->recvTime : null,
                    isset($message->gwRecvTime) ? $message->gwRecvTime : null,
                    isset($message->delayed) ? ($message->delayed === true ? 1 : 0) : null,
                    isset($message->ulFrequency) ? $message->ulFrequency : null,
                    isset($message->modulation) ? $message->modulation : null,
                    isset($message->dataRate) ? $message->dataRate : null,
                    isset($message->codingRate) ? $message->codingRate : null,
                    isset($message->gwCnt) ? $message->gwCnt : null,
                    $receivedIn,

                    isset($message->classB) ? ($message->classB === true ? 1 : 0) : null,
                    isset($message->fPort) ? $message->fPort : null
                ]
            );

            $insertedGwInfo = true;
            if (isset($message->gwInfo))
            {
                $idDataUpDto = $this->pdo->lastInsertId();
        
                foreach ($message->gwInfo as $gwInfo)
                {
                    $sqlGwInfo = "INSERT INTO ".$this->config->database.".gwInfo
                            (
                                gwEui,
                                rfRegion,
                                rssi,
                                snr,
                                latitude,
                                longitude,
                                channel,
                                radioId,
            
                                DataUpDto_id
                            )
                            VALUES (?,?,?,?,?,?,?,?,?)";
                    $stmt= $this->pdo->prepare($sqlGwInfo);
                    $insertedGwInfo = $stmt->execute(
                        [
                            $gwInfo->gwEui,
                            $gwInfo->rfRegion,
                            $gwInfo->rssi,
                            $gwInfo->snr,
                            $gwInfo->latitude,
                            $gwInfo->longitude,
                            $gwInfo->channel,
                            $gwInfo->radioId,
                            $idDataUpDto
                        ]
                    );
                }
            }

            $this->pdo->commit();
            return $insertedDataUpDto && $insertedGwInfo;
        }
        catch (\Exception $e)
        {
            $this->pdo->rollBack();
            Logger::getInstance()->log('Error: ' . (($json = json_encode($e, JSON_PRETTY_PRINT)) ? $json : $e->getMessage()));
            return false;
        }
    }
}