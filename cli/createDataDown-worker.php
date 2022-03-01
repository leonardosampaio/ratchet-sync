<?php

require_once __DIR__ . '/../vendor/autoload.php';

use app\CurlWrapper;
use app\Configuration;
use app\Persistence;

$applicationConfig = (new Configuration())->getApplication();

if (isset($applicationConfig->error))
{
    die($applicationConfig->error);
}

$persistence = new Persistence($applicationConfig->mysql);

$url = '/gms/application/dataDown';

$triggers = $persistence->getDataDownTriggers();

if (empty($triggers))
{
    die('No unprocesed triggers found');
}

foreach($triggers as $trigger)
{
    //TODO payload
    $objPayload = [
        "fPort" => 0,
        "confirmed" => true,
        "payload" => "string",
        "contentType" => "string",
        "endDevice" => [
            "devAddr" => $trigger->endDevice_devAddr,
            "devEui" => $trigger->endDevice_devEui
        ]
    ];
    
    $result = (new CurlWrapper())->post(
        $applicationConfig->baseUrl . $url,
        [],
        json_encode($objPayload),
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $applicationConfig->token->value
        ],
        $applicationConfig->port ?? 443
    );

    if (201 === $result['httpCode'])
    {
        $persistence->setDataDownTriggerProcessed($trigger->id);
        echo 'Created data down for trigger ' . $trigger->id . PHP_EOL;
    }
    else {
        echo 'Failed to create data down for trigger ' . $trigger->id . PHP_EOL;
    }
}