<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/Email.php';
require_once ENDEAVOUR_DIR . 'lib/models/Session.php';

// POST route
$endeavour->post(
    '/email',
    function () use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $request = $_POST;

        $templateID = 0;

        switch ($request['Type']) {

            case 'feedback':

                // Check for missing fields
                if (!array_key_exists('Feedback', $request) || !trim($request['Feedback'])) {
                    throw new Exception('Please enter a message', '400::fields_missing');
                }

                // Set template ID
                $templateID = 1;

                // Set recipient name/email
                $recipientName = 'EndeavourApp Feedback';
                $recipientEmail = 'feedback@endeavourapp.com';

                // Set replacement vars
                $replacements['FromUserID'] = $endeavour->Gatekeeper->Session->get('UserID');
                $replacements['Rating'] = array_key_exists('Rating', $request) ? $request['Rating'] : '?';
                $replacements['Feedback'] = $request['Feedback'];

                break;

        }

        if (!$templateID) {
            throw new Exception('Invalid request, no such email type', 400);
        }

        $email = new Email;

        $email->useTemplate($templateID)
              ->setReplacements($replacements)
              ->setRecipient($recipientEmail, $recipientName)
              ->send();

        // Return EmailID
        echo json_encode([
            'EmailID' => $email->getID(),
        ]);

    }
);
