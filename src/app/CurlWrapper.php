<?php

namespace app;

class CurlWrapper {

    /**
     * Single thread POST request
     */
    public function post($url, $post = [], $body = '', $headers = [], $port = 443)
    {
        $consumer = curl_init();

        if (!empty($post))
        {
            curl_setopt($consumer, CURLOPT_POSTFIELDS, $post);
        }

        if (!empty($headers))
        {
            curl_setopt($consumer, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($body))
        {
            curl_setopt($consumer, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($consumer, CURLOPT_URL, $url);
        curl_setopt($consumer, CURLOPT_PORT, $port);
        
        curl_setopt($consumer, CURLOPT_HEADER, 0);
        curl_setopt($consumer, CURLOPT_POST, 1); 
        curl_setopt($consumer, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($consumer, CURLOPT_SSL_VERIFYPEER, 1);

        $response = curl_exec($consumer);
        $httpcode = curl_getinfo($consumer, CURLINFO_HTTP_CODE);

        if (curl_errno($consumer))
        { 
            $response = curl_error($consumer);
            curl_close($consumer); 
        }

        return [
            'httpCode'=>$httpcode,
            'response'=>$response
        ];
    }
}