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
        file_get_contents(__DIR__.'/../schema/createDataDown_test.json'));

    $objPayload->fPort =    $trigger->fPort;
    $objPayload->endDevice->devEui =   $trigger->devEui;
    $objPayload->payload =  $trigger->payload;
    
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
        echo 'Response: ' . $result['response'] . PHP_EOL;
    }
}