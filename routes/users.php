<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/User.php';

// GET route
$endeavour->get(
    '/users/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        if ($ID == 0) {
            // Set ID to current UserID
            $ID = $endeavour->Gatekeeper->Session->get('UserID');
        }

        $user = new Model\User($ID);

        if (!$user->exists()) {
            $endeavour->notFound();
        }
        
        if ($user->getID() != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        echo $user->toJSON();

    }
);

// PATCH route
$endeavour->patch(
    '/users/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get User
        $user = new Model\User($ID);

        if (!$user->exists()) {
            $endeavour->notFound();
        }
        
        if ($user->getID() != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $request = json_decode($endeavour->request()->getBody(), true);

        $attrs = [];

        if (array_key_exists('FirstName', $request)) {
            $attrs['FirstName'] = $request['FirstName'];
        }

        if (array_key_exists('LastName', $request)) {
            $attrs['LastName'] = $request['LastName'];
        }

        if (array_key_exists('TimeZone', $request)) {
            $zones = \DateTimeZone::listIdentifiers();
            if (in_array($request['TimeZone'], $zones)) {
                $attrs['TimeZone'] = $request['TimeZone'];
            }
        }

        if (count($attrs) > 0 ) {
            $user->setMultiple($attrs)
                 ->save();
        }

        // Return List
        echo $user->toJSON();

    }
);

// POST change-password route
$endeavour->post(
    '/users/:ID/change-password',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $user = new Model\User($ID);

        if (!$user->exists()) {
            $endeavour->notFound();
        }
        
        if ($user->getID() != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        // Some quick input validation
        if (!$user->checkPassword($currentPassword)) {
            throw new Exception('Incorrect password', '400::incorrect_password');
        }

        if (strlen($newPassword) < 7) {
            throw new Exception('New password must be at least 7 characters', '400::invalid_new_password');
        }

        if ($newPassword != $confirmPassword) {
            throw new Exception('Passwords do not match', '400::password_mismatch');
        }

        // Attempt to change password
        $user->changePassword($newPassword);

        // Return user model
        echo $user->toJSON();

    }
);

// POST change-email route
$endeavour->post(
    '/users/:ID/change-email',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $user = new Model\User($ID);

        if (!$user->exists()) {
            $endeavour->notFound();
        }
        
        if ($user->getID() != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $currentPassword = $_POST['currentPassword'];
        $newEmailAddress = $_POST['newEmailAddress'];
        $confirmEmailAddress = $_POST['confirmEmailAddress'];

        // Some quick input validation
        if (!$user->checkPassword($currentPassword)) {
            throw new Exception('Incorrect password', '400::incorrect_password');
        }

        if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $newEmailAddress)) {
            throw new Exception('Invalid Email Address', '400::invalid_new_email');
        }

        if ($newEmailAddress != $confirmEmailAddress) {
            throw new Exception('Email addresses do not match', '400::email_mismatch');
        }

        // CHECK NEW EMAIL ADDRESS DOES NOT ALREADY EXIST

        // Attempt to change email address
        $user->changeEmailAddress($newEmailAddress);

        // Return user model
        echo $user->toJSON();

    }
);

// POST verify-email route
$endeavour->post(
    '/users/:ID/verify-email',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $user = new Model\User($ID);

        if (!$user->exists()) {
            $endeavour->notFound();
        }
        
        if ($user->getID() != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $newEmailAddress = $_POST['newEmailAddress'];
        $verificationCode = $_POST['verificationCode'];

        // Some quick input validation
        if (!$verificationCode || strlen($verificationCode) != 10) {
            throw new Exception('Invalid verification code', '400::invalid_verification_code');
        }

        // Attempt to verify email address
        // If the address and code match, the user email address will be updated
        $user->verifyEmailAddress($newEmailAddress, $verificationCode);

        // Return user model
        echo $user->toJSON();

    }
);