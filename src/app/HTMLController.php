<?php

namespace app;

class HTMLController {

    private $applicationConfig;

    public function __construct($applicationConfig) {
        $this->applicationConfig = $applicationConfig;
    }

    public function getHtmlFromTemplate($templateName, $headerAndFooter = true)
    {
        if ($headerAndFooter)
        {
            require __DIR__.'/../../public/templates/header.html';
        }
        
        require __DIR__.'/../../public/templates/'.$templateName.'.html';

        if ($headerAndFooter)
        {
            require __DIR__.'/../../public/templates/footer.html';
        }
        die();
    }
}