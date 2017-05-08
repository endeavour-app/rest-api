<?php

namespace Endeavour\Error;

require_once ENDEAVOUR_DIR . 'lib/Error.php';

final class NotFound extends \Endeavour\Error 
{

    static $responseCode = 404;

    private $error = 'resource_not_found';
    private $description = 'No resource was found at this URL.';

    public function toArray()
    {
        return [
            'error'                 => $this->error,
            'error_description'     => $this->description
        ];
    }

}