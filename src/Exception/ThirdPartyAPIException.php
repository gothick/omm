<?php

namespace App\Exception;

use Exception;

class ThirdPartyAPIException extends Exception
{
    public function __construct(string $errorMessage, int $errorCode, private readonly mixed $thirdPartyErrorDetails)
    {
        parent::__construct($errorMessage, $errorCode);
    }

    public function getThirdPartyErrorDetails(): mixed
    {
        return $this->thirdPartyErrorDetails;
    }
}
