<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/Session.php';

// GET route
$endeavour->post(
    '/check-in',
    function () use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $request = json_decode($endeavour->request()->getBody(), true);

        /* 

            parse something like...

            {
              LastDate: '2014-04-15T00:23:52Z',
              Current: {
                ListID: 123,
                ListItemID: 4567
              }
            }

        */

        /* 

            return something like...

            {
              Date: '2014-04-15T00:24:22Z',
              ValidSession: true,
              Session: {session},
              Changes: [],
            }

        */

        $response = [];

        if ($endeavour->Gatekeeper->Session->isValid()) {
            $response['ValidSession'] = true;
            $response['Session'] = $endeavour->Gatekeeper->Session->toArray();
        }

        $response = $endeavour->Gatekeeper->Session->toArray();

        echo json_encode($response);

    }
);
