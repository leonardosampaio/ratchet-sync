<?php

namespace websocket;

class Logger {
    
    private static $instance;
    private $modes;
    private $outputFile;

    public static function getInstance()
    {
        if(self::$instance === null){
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct(){
        //singleton
    }

    public function setOutputFile($file)
    {
        $mode = $this->getModes();
        if (!in_array('file', $mode))
        {
            $mode[] = 'file';
            $this->setModes($mode);
        }
        $this->outputFile = $file;
    }

    public function setModes($modes = [])
    {
        if (empty($modes) || sizeof(array_diff($modes, ['stdout', 'file']))>0)
        {
            die('Invalid modes: ' . implode(',', $modes));
        }
        $this->modes = $modes;
    }

    public function getModes()
    {
        if ($this->modes == null)
        {
            $this->modes = ['stdout'];
        }
        return $this->modes;
    }

    public function log($msg)
    {
        if (in_array('stdout', $this->getModes()))
        {
            $separator = php_sapi_name() == "cli" ? PHP_EOL : '<br>';
            $echoFormat = "[%s] %s" . $separator;
            $echoFormatted = sprintf($echoFormat, date('Y-m-d H:i:s'), $msg);
            
            echo $echoFormatted;
        }

        if (in_array('file', $this->getModes()) && $this->outputFile)
        {
            $fileFormat = "[%s] %s" . PHP_EOL;
            $fileFormatted = sprintf($fileFormat, date('Y-m-d H:i:s'), $msg);

            file_put_contents($this->outputFile, $fileFormatted, FILE_APPEND);
        }
    }
}