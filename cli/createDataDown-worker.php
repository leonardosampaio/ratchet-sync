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
    $objPayload = json_decode(
        file_get_contents(__DIR__.'/../schema/createDataDown_complete.json'));

    $objPayload->fPort =                $trigger->fPort;
    $objPayload->confirmed =            $trigger->confirmed == 1;
    $objPayload->endDevice->devEui =    $trigger->devEui;
    $objPayload->payload =              $trigger->payload;
    $objPayload->contentType =          $trigger->contentType;
    
    $request = json_encode($objPayload);

    $result = (new CurlWrapper())->post(
        $applicationConfig->baseUrl . $url,
        [],
        $request,
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $applicationConfig->token->value
        ],
        $applicationConfig->port ?? 443
    );

    echo 'Request: ' . $request . PHP_EOL;

    if (201 === $result['httpCode'])
    {
        $persistence->setDataDownTriggerProcessed($trigger->id);
        echo 'Created data down for trigger ' . $trigger->id . PHP_EOL;
    }
    else {
        echo 'Failed to create data down for trigger ' . $trigger->id . PHP_EOL;
    }

    echo 'Response: ' . json_encode($result) . PHP_EOL;
}