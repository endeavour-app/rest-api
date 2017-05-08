<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/Session.php';

// POST route
$endeavour->post(
    '/logout',
    function () use ($endeavour) {

        // Assert valid session
        $endeavour->Gatekeeper->assertValidSession();

        // Revoke session access
        $endeavour->Gatekeeper->Session->revoke();

        // Return session
        echo $session->toJSON();

    }
);

// POST route
$endeavour->post(
    '/login',
    function () use ($DB, $endeavour) {

        $request = json_decode($endeavour->request()->getBody(), true);

        if (!array_key_exists('EmailAddress', $request)) {
            throw new Exception('EmailAddress not provided', '400::invalid_login');
        }

        if (!array_key_exists('Password', $request)) {
            throw new Exception('Password not provided', '400::invalid_login');
        }

        $query = $DB->Prepare(
            'SELECT *
            FROM Users
            WHERE LOWER(EmailAddress) = LOWER(?)'
        );

        $record = $DB->GetRow(
            $query,
            array(
                $request['EmailAddress']
            )
        );

        if (count(array_keys($record)) < 1) {
            throw new Exception('Email address not registered', '400::invalid_login');
        }

        if ($record['Password'] != Utils::hashPassword($request['Password'])) {
            throw new Exception('Invalid login', '400::invalid_login');
        }

        // Create session
        $session = new Model\Session();

        $session->setUserID($record['ID'])
                ->setExpiry(60*60*24*365)
                ->generateKey()
                ->create();

        // Return session
        echo $session->toJSON();

    }
);
