<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/errors/NotFound.php';

$endeavour->notFound(function () {
    
    $error = new Error\NotFound();
    
    http_response_code($error->getResponseCode());
    
    echo $error->toJSON();
    exit;
    
});
