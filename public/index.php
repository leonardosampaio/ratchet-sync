<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use app\HTMLController;
use app\CurlWrapper;
use app\Configuration;

$applicationConfig = (new Configuration())->getApplication();

if (isset($applicationConfig->error))
{
    die($applicationConfig->error);
}

if (empty($applicationConfig))
{
    die('Invalid application configuration');
}

ini_set('max_execution_time', '0');

$app = AppFactory::create();

//if in subpath this needs to be different from "/"
if (isset($applicationConfig->serverPath))
{
    $app->setBasePath($applicationConfig->serverPath);
}

$app->get('/', function () use ($applicationConfig)
{
    (new HTMLController($applicationConfig))->getHtmlFromTemplate('login', false);
});

$app->get('/test-socket', function () use ($applicationConfig)
{
    (new HTMLController($applicationConfig))->getHtmlFromTemplate('test-socket', false);
});

$app->get('/home', function (Request $request, Response $response) use ($applicationConfig, $app)
{
    session_start();

    if (empty($_SESSION['user']))
    {
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    (new HTMLController($applicationConfig))->getHtmlFromTemplate('home');
});

$app->get('/configuration/{type}', function (Request $request, Response $response, array $args) use ($applicationConfig, $app)
{
    session_start();

    if (empty($_SESSION['user']))
    {
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    $type = $args['type'];

    return $response->withJson(
        json_decode(
            file_get_contents(__DIR__."/../schema/createDataDown_$type.json")));
});


$app->post('/create-data-down', function(Request $request, Response $response) use ($applicationConfig)
{
    session_start();

    $url = '/gms/application/dataDown';

    if (empty($_SESSION['user']))
    {
        return $response->withJson(
        [
            'success' => false,
            'errors'=>[
                'Session expired']], 400);
    }

    $rawPayload = file_get_contents('php://input');
    $objPayload = @json_decode($rawPayload);

    if (!isset($objPayload->fPort) ||
        !is_int($objPayload->fPort) ||
        !isset($objPayload->confirmed) ||
        !is_bool($objPayload->confirmed) ||
        empty($objPayload->payload) ||
        !is_string($objPayload->payload) ||
        empty($objPayload->contentType) ||
        !is_string($objPayload->contentType))
    {
        return $response->withJson(
            [
                'success' => false,
                'errors'=>[
                    'Invalid request. Required: fPort (integer), confirmed (boolean), payload (string), contentType (string)']], 400);
    }

    // https://wikikerlink.fr/wanesy-ran/doku.php?id=wiki:wiki3:gms_api
    $token = ($_SESSION['user'])->token;

    $result = (new CurlWrapper())->post(
        $applicationConfig->baseUrl . $url,
        [],
        json_encode($objPayload),
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        $applicationConfig->port ?? 443
    );

    $responseArray = [
        'success' => true,
        'httpCode' => $result['httpCode'],
        'response'=> @json_decode($result['response'])
    ];

    if (201 !== $result['httpCode'])
    {
        $responseArray['success'] = false;
        $responseArray['errors'] = ['Unexpected response'];
    }

    return $response->withJson($responseArray)->withStatus(200);
});

$app->post('/login', function(Request $request, Response $response) use ($applicationConfig)
{
    session_start();

    $url = '/gms/application/login';

    $rawPayload = file_get_contents('php://input');
    $objPayload = json_decode($rawPayload);

    if (empty($objPayload->login) ||
        empty($objPayload->password))
    {
        return $response->withJson(
            ['errors'=>[
                'Invalid payload']], 400);
    }

    $result = (new CurlWrapper())->post(
        $applicationConfig->baseUrl . $url,
        [],
        json_encode($objPayload),
        [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        $applicationConfig->port ?? 443
    );

    if (201 !== $result['httpCode'])
    {
        return $response->withJson(
            [
                'success' => false,
                'errors'=>['unexpected response'],
                'httpCode' => $result['httpCode'],
                'response'=> json_decode($result['response'])

            ])->withStatus(400);
    }

    $_SESSION['user'] = json_decode($result['response']);

    return $response->withJson(
        [
        'success' => true,
        'httpCode' => $result['httpCode'],
        'message' => 'Authenticated'
        ])->withStatus(200);
});

$app->run();