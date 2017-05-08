<?php

namespace Endeavour\Error;

require_once ENDEAVOUR_DIR . 'lib/Error.php';

final class BadRequest extends \Endeavour\Error 
{

    static $responseCode = 400;

    private $error = 'bad_request';
    private $description = 'Request was malformed or incomplete.';

    function __construct($error = null, $description = null) 
    {
        if ($error !== null) $this->error = $error;
        if ($description !== null) $this->description = $description;
    }

    public function toArray()
    {
        return [
            'error'                 => $this->error,
            'error_description'     => $this->description
        ];
    }

}