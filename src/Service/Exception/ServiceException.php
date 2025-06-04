<?php

namespace App\VeryBadSplit\Service\Exception;

use Exception;

class ServiceException extends Exception{
    
    private string $redirectionUrl;
    private string $typeMessageFlash;
    
    public function __construct(string $message, string $redirectionUrl, string $typeMessageFlash = "danger") {
        parent::__construct($message);
        $this->redirectionUrl = $redirectionUrl;
        $this->typeMessageFlash = $typeMessageFlash;
    }
    
    public function getRedirectionUrl(): string {
        return $this->redirectionUrl;
    }
    
    public function getTypeMessageFlash(): string {
        return $this->typeMessageFlash;
    }
    
}