<?php

namespace app;

use stdClass;

class Configuration {

    private function getJsonFromConfigFile($filePath)
    {
        $result = new stdClass();

        if (!file_exists($filePath) || !is_readable($filePath))
        {
            $result->error = "Configuration $filePath not found or not readable";
            return $result;
        }

        return json_decode(file_get_contents($filePath));
    }
    
    public function getApplication()
    {
        $configObj = $this->getJsonFromConfigFile(__DIR__.'/../../configuration/app.json');

        if ($configObj->token &&
            $configObj->token->expiredDate &&
            $configObj->login && 
            $configObj->password && 
            $configObj->token->expiredDate - time()*1000 < 0)
        {
            $url = '/gms/application/login';

            $objPayload = [
                'login' => $configObj->login,
                'password' => $configObj->password
            ];

            $result = (new CurlWrapper())->post(
                $configObj->baseUrl . $url,
                [],
                json_encode($objPayload),
                [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                $configObj->port ?? 443
            );
        
            if (201 === $result['httpCode'])
            {
                $response = json_decode($result['response']);

                $configObj->token = [
                    'value' => $response->token,
                    'expiredDate' => $response->expiredDate
                ];

                file_put_contents(__DIR__.'/../../configuration/app.json',
                    json_encode($configObj, JSON_PRETTY_PRINT));
            }
        }

        return $configObj;
    }

    public function updateCredentials($login, $password, $token, $expiredDate)
    {
        if (!$login || !$password || !$token || !$expiredDate)
        {
            return;
        }

        $applicationConfig = $this->getApplication();

        if (isset($applicationConfig->error))
        {
            return $applicationConfig->error;
        }

        $applicationConfig->token = [
            'value' => $token,
            'expiredDate' => $expiredDate
        ];

        $applicationConfig->login = $login;
        $applicationConfig->password = $password;

        file_put_contents(__DIR__.'/../../configuration/app.json',
            json_encode($applicationConfig, JSON_PRETTY_PRINT));
    }
}