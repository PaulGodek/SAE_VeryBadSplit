<?php

namespace App\VeryBadSplit\Service\Exception;

use Exception;

class ServiceException extends Exception{
    
    private string $redirectionRoute;
    private string $typeMessageFlash;
    private array $arguments;

    public function __construct(string $message, int $code,string $redirectionRoute, array $arguments = [], string $typeMessageFlash = "danger") {
        parent::__construct($message, $code);
        $this->redirectionRoute = $redirectionRoute;
        $this->arguments = $arguments;
        $this->typeMessageFlash = $typeMessageFlash;
    }
    
    public function getRedirectionRoute(): string {
        return $this->redirectionRoute;
    }
    
    public function getTypeMessageFlash(): string {
        return $this->typeMessageFlash;
    }

    public function getArguments(): array {
        return $this->arguments;
    }

}