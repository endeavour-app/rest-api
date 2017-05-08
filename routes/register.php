<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/User.php';

// POST route
$endeavour->post(
    '/register',
    function () use ($endeavour, $DB) {

        $requiredFieldKeys = ['FirstName','LastName','EmailAddress','ConfirmEmail'];
        $postedFieldKeys = array_keys($_POST);

        foreach ($requiredFieldKeys as $key) {
            if (!in_array($key, $postedFieldKeys) || !$_POST[$key]) {
                throw new Exception($key . ' not provided', '400::fields_missing');
            }
        }

        // Prepare values
        $FirstName = trim($_POST['FirstName']);
        $LastName = trim($_POST['LastName']);
        $EmailAddress = strtolower(trim($_POST['EmailAddress']));
        $ConfirmEmail = strtolower(trim($_POST['ConfirmEmail']));
        $TimeZone = 'UTC';

        if (array_key_exists('TimeZone', $_POST)) {
            $zones = \DateTimeZone::listIdentifiers();
            if (in_array($_POST['TimeZone'], $zones)) {
                $TimeZone = $_POST['TimeZone'];
            }
        }

        // Ensure valid email address
        if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $EmailAddress)) {
            throw new Exception('Invalid Email Address', '400::invalid_email');
        }

        // Ensure email addresses match
        if ($EmailAddress != $ConfirmEmail) {
            throw new Exception('Email addresses do not match', '400::email_mismatch');
        }

        // Look for an existing account
        $query = $DB->Prepare(
            'SELECT *
            FROM Users
            WHERE LOWER(EmailAddress) = LOWER(?)'
        );

        $result = $DB->GetRow(
            $query,
            array(
                $EmailAddress
            )
        );

        if ($result && $result['ID']) {
            throw new Exception('Email address already registered', '400::email_already_registered');
        }

        // Generate a new password
        $Password = substr(strtoupper(md5(time())), rand(1,16), 8);

        // Create new user
        $user = new Model\User();

        $user->set('FirstName', $FirstName)
             ->set('LastName', $LastName)
             ->set('Password', $Password)
             ->set('EmailAddress', $EmailAddress)
             ->set('TimeZone', $TimeZone)
             ->create();

        // Send welcome email
        $user->sendWelcomeEmail();

        // Subscribe to mailing list
        // if (array_key_exists('MailingList', $_POST) && $_POST['MailingList']) {
        //     $user->subscribeToNewsletter();
        // }

        // Return user
        echo json_encode(array_merge($user->toArray(), ['NewPassword' => $Password]));

    }
);
