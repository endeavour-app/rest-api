<?php

namespace Endeavour\Error;

require_once ENDEAVOUR_DIR . 'lib/Error.php';

final class Internal extends \Endeavour\Error 
{

    static $responseCode = 500;

    private $error = 'internal_server_error';
    private $description = 'The server encountered an unexpected condition which prevented it from fulfilling the request.';

    public function toArray()
    {
        return [
            'error'                 => $this->error,
            'error_description'     => $this->description
        ];
    }

}