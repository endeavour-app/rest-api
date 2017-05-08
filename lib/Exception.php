<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/errors/BadRequest.php';
require_once ENDEAVOUR_DIR . 'lib/errors/Internal.php';
require_once ENDEAVOUR_DIR . 'lib/errors/NotFound.php';

class Exception extends \Exception
{
    private $error;

    public function __construct($message, $code = 0, Exception $previous = null)
    {
        $httpResponseCode = $code;
        $apiErrorCode = null;

        if (is_string($code)) {
            list($httpResponseCode, $apiErrorCode) = explode('::', $code);
        }

        switch ((int) $httpResponseCode) {
            case 400:
                $this->error = new \Endeavour\Error\BadRequest($apiErrorCode, $message);
                break;
            case 404:
                $this->error = new \Endeavour\Error\NotFound();
                break;
            case 500:
                //no break
            default:
                $this->error = new \Endeavour\Error\Internal();
                break;
        }

        parent::__construct($message, $httpResponseCode, $previous);
    }

    public function __toString()
    {
        return "Endeavour Exception: [{$this->code}]: {$this->message}\n";
    }

    public function toJSON()
    {
        return $this->error->toJSON();
    }

    public function printJSON()
    {
        echo $this->toJSON();
    }

    public function getResponseCode()
    {
        return $this->error->getResponseCode();
    }
}