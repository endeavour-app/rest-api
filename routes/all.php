<?php

// Errors & core routes
require_once ENDEAVOUR_DIR . 'routes/404.php';

// Special routes
require_once ENDEAVOUR_DIR . 'routes/login.php';
require_once ENDEAVOUR_DIR . 'routes/register.php';
require_once ENDEAVOUR_DIR . 'routes/email.php';
require_once ENDEAVOUR_DIR . 'routes/timezones.php';
require_once ENDEAVOUR_DIR . 'routes/check-in.php';

// Model routes
require_once ENDEAVOUR_DIR . 'routes/lists.php';
require_once ENDEAVOUR_DIR . 'routes/listitems.php';
require_once ENDEAVOUR_DIR . 'routes/sessions.php';
require_once ENDEAVOUR_DIR . 'routes/users.php';

$endeavour->options('/.+', function () use ($endeavour) {
    //Return response headers
    $endeavour->response->setStatus(200);
});