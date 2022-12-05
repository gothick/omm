<?php

namespace App\Exception;

use Exception;

class ThirdPartyAPIException extends Exception
{
    /** @var mixed */
    private $thirdPartyErrorDetails;

    public function __construct(string $errorMessage, int $errorCode, mixed $errorDetails)
    {
        parent::__construct($errorMessage, $errorCode);
        $this->thirdPartyErrorDetails = $errorDetails;
    }

    public function getThirdPartyErrorDetails(): mixed
    {
        return $this->thirdPartyErrorDetails;
    }
}
