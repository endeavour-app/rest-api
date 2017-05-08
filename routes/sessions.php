<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/Session.php';

// GET route
$endeavour->get(
    '/sessions/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        if ($ID == 0) {
            // Return current session
            echo $endeavour->Gatekeeper->Session->toJSON();
            return;
        }

        $session = new Model\Session($ID);

        if (!$session->exists()) {
            $endeavour->notFound();
        }

        if ($session->get('UserID') != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        echo $session->toJSON();

    }
);
