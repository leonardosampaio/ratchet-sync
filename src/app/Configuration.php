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
        return $this->getJsonFromConfigFile(__DIR__.'/../../configuration/app.json');
    }

    public function updateCredentials($login, $password, $token, $expiredDate)
    {
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

        file_put_contents(__DIR__.'/../../configuration/app.json', json_encode($applicationConfig, JSON_PRETTY_PRINT));
    }
}