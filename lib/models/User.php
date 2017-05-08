<?php

namespace Endeavour\Model;

require_once ENDEAVOUR_DIR . 'lib/Model.php';
require_once ENDEAVOUR_DIR . 'lib/Utils.php';

class User extends \Endeavour\Model
{    
    protected $EmailAddress;
    
    protected $Password;
    
    protected $FirstName;
    
    protected $LastName;
    
    protected $TimeZone;
    
    protected $Created;
    
    protected $Modified;
    
    public function get($key)
    {
        $value = null;
        
        switch ($key) {
            case 'ID':
                if (!$this->exists()) {
                    throw new \Endeavour\Exception('Unable to get ID, user does not exist');
                }
                $value = $this->ID;
                break;
            case 'EmailAddress':
                $value = $this->EmailAddress;
                break;
            case 'Password':
                $value = $this->Password;
                break;
            case 'FirstName':
                $value = $this->FirstName;
                break;
            case 'LastName':
                $value = $this->LastName;
                break;
            case 'TimeZone':
                $value = $this->TimeZone;
                break;
            case 'Created':
                $value = $this->Created;
                break;
            case 'Modified':
                $value = $this->Modified;
                break;
            default:
                throw new \Endeavour\Exception("Unable to get $key, $key is not gettable");
                break;
        }
        
        return $value;
    }
    
    public function set($key, $value)
    {
        switch ($key) {
            case 'EmailAddress':
                if ($this->exists()) {
                    throw new \Endeavour\Exception('Unable to set Email Address, user record already exists');
                }
                $this->EmailAddress = $value;
                break;
            case 'Password':
                if ($this->exists()) {
                    throw new \Endeavour\Exception('Unable to set Password, user record already exists');
                }
                $this->Password = \Endeavour\Utils::hashPassword($value);
                $this->PlainTextPassword = $value;
                break;
            case 'FirstName':
                $this->FirstName = $value;
                break;
            case 'LastName':
                $this->LastName = $value;
                break;
            case 'TimeZone':
                $this->TimeZone = $value;
                break;
            default:
                throw new \Endeavour\Exception("Unable to set $key, $key is not settable");
                break;
        }

        $this->changedVars[] = $key;
        
        return $this;
    }
    
    public function load()
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('Unable to load user, no ID provided');
        }
        
        $query = $this->DB->Prepare(
            'SELECT *
            FROM Users
            WHERE ID = ?'
        );

        $record = $this->DB->GetRow(
            $query,
            array(
                $this->ID
            )
        );

        if (count(array_keys($record)) < 1) {
            throw new \Endeavour\Exception('Unable to load user, database returned empty result');
        }

        return $this->_setProps($record);
    }

    protected function _setProps($props)
    {
        $this->ID = (int) $props['ID'];
        $this->EmailAddress = $props['EmailAddress'];
        $this->Password = $props['Password'];
        $this->FirstName = $props['FirstName'];
        $this->LastName = $props['LastName'];
        $this->TimeZone = $props['TimeZone'];

        $this->Created = new \DateTime(
            $props['Created'],
            new \DateTimeZone('UTC')
        );

        if (array_key_exists('Modified', $props) && $props['Modified']) {
            $this->Modified = new \DateTime(
                $props['Modified'],
                new \DateTimeZone('UTC')
            );
        }

        return $this;
    }
    
    public function save()
    {
        if (!$this->exists()) {
            return $this->create();
        }

        $changedVars = $this->_getChangedVars();

        if (count($changedVars) < 1) return $this;

        $set = [];
        $replacements = [$this->ID];

        foreach ($changedVars as $key) {
            $value = $this->{$key};
            $setSQL = "`$key` = ?";
            if ($value instanceof \DateTime) $value = $value->format('Y-m-d H:i:s');
            array_unshift($set, $setSQL);
            array_unshift($replacements, $value);
        }

        $set = implode(', ', $set);

        $query = $this->DB->Prepare(
            "UPDATE `Users`
            SET $set
            WHERE `ID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            $replacements
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to save user, database error');
        }

        $this->load()
             ->_clearChangedVars();

        return $this;
    }
    
    public function create()
    {
        if ($this->exists()) {
            throw new \Endeavour\Exception('Unable to create user, user already exists');
        }

        $query = $this->DB->Prepare(
            'INSERT INTO `Users`
            ( `EmailAddress`, `Password`, `FirstName`, `LastName`, `TimeZone`, `Created` )
            VALUES
            ( ?, ?, ?, ?, ?, UTC_TIMESTAMP() )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->EmailAddress,
                $this->Password,
                $this->FirstName,
                $this->LastName,
                $this->TimeZone,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to create user, database error');
        }

        $this->ID = $this->DB->Insert_ID();

        return $this->load();
    }
    
    public function delete()
    {
        return $this;
    }

    public function checkPassword($Password)
    {
        return $this->Password == \Endeavour\Utils::hashPassword($Password);
    }

    public function sendWelcomeEmail()
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('User account must exist before welcome email can be sent.');
        }

        // Set template ID
        $templateID = 4;

        // Set recipient name/email
        $recipientName = join(' ', [$this->FirstName, $this->LastName]);
        $recipientEmail = $this->EmailAddress;

        // Set replacement vars
        $replacements['FirstName'] = $this->FirstName;
        $replacements['FullName'] = $this->FirstName . ' ' . $this->LastName;
        $replacements['EmailAddress'] = $this->EmailAddress;
        $replacements['Password'] = $this->PlainTextPassword;

        $email = new \Endeavour\Email;

        $email->useTemplate($templateID)
              ->setReplacements($replacements)
              ->setRecipient($recipientEmail, $recipientName)
              ->send();

        return $this;
    }

    public function verifyEmailAddress($newAddress, $code)
    {
        $query = $this->DB->Prepare(
            "SELECT *
            FROM `EmailVerificationCodes`
            WHERE `UserID` = ?
            AND `EmailAddress` = ?
            AND `VerificationCode` = ?"
        );

        $result = $this->DB->GetRow($query, [$this->ID, $newAddress, $code]);

        if (!$result) {
            throw new \Endeavour\Exception('Invalid verification code', '400::invalid_verification_code');
        }

        $query = $this->DB->Prepare(
            'UPDATE `Users`
            SET `EmailAddress` = ?
            WHERE `ID` = ?'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $newAddress,
                $this->ID,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to update email address, database error', 500);
        }
        
        return $this->load();
    }
    
    public function changeEmailAddress($newAddress, $confirmAddress = null)
    {
        if ($confirmAddress !== null && strtolower($newAddress) != strtolower($confirmAddress)) {
            throw new \Endeavour\Exception('Unable to update Email Address, address doesn\'t match confirmation', 400);
        }
        
        // Create 10-digit code
        $codeLength = 10;
        $code = rand(pow(10, $codeLength - 1), pow(10, $codeLength) - 1);

        // Look for an existing account
        $query = $DB->Prepare(
            'SELECT *
            FROM Users
            WHERE LOWER(EmailAddress) = LOWER(?)'
        );

        $result = $DB->GetRow(
            $query,
            array(
                $newAddress
            )
        );

        if ($result && $result['ID']) {
            throw new \Endeavour\Exception('Email address already registered', '400::email_already_registered');
        }

        // Store it in the DB
        $query = $this->DB->Prepare(
            "INSERT INTO `EmailVerificationCodes` 
            (`UserID`, `EmailAddress`, `VerificationCode`, `Created`) 
            VALUES (?, ?, ?, UTC_TIMESTAMP())"
        );
        $insert = $this->DB->Execute($query, [$this->ID, $newAddress, $code]);

        if (!$insert) {
            throw new \Endeavour\Exception('Unable to create verification code', 500);
        }

        // Send the user an Email with the code
        $this->sendEmailVerificationCode($newAddress, $code);
        
        return $this;
    }

    private function sendEmailVerificationCode($emailAddress, $code)
    {
        // Set template ID
        $templateID = 3;

        // Set recipient name/email
        $recipientName = join(' ', [$this->FirstName, $this->LastName]);
        $recipientEmail = $emailAddress;

        // Set replacement vars
        $replacements['FirstName'] = $this->FirstName;
        $replacements['Code'] = $code;

        $email = new \Endeavour\Email;

        $email->useTemplate($templateID)
              ->setReplacements($replacements)
              ->setRecipient($recipientEmail, $recipientName)
              ->send();

        return $this;
    }
    
    public function changePassword($newPassword, $confirmPassword = null)
    {
        if ($confirmPassword !== null && $newPassword != $confirmPassword) {
            throw new \Endeavour\Exception('Unable to update Password, password doesn\'t match confirmation', '400::password_mismatch');
        }

        $query = $this->DB->Prepare(
            'UPDATE `Users`
            SET `Password` = ?
            WHERE `ID` = ?'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                \Endeavour\Utils::hashPassword($newPassword),
                $this->ID,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to update password, database error');
        }
        
        return $this->load();
    }

    public function getAvatarURL($https = true)
    {
        return ($https ? 'https://secure.' : 'http://www.') . 'gravatar.com/avatar/' . md5(strtolower($this->EmailAddress)) . '?s=128&d=mm&r=pg';
    }
    
    public function toArray()
    {
        return [
            'ID'             => $this->ID,
            'EmailAddress'   => $this->EmailAddress,
            'AvatarURL'      => $this->getAvatarURL(),
            'FirstName'      => $this->FirstName,
            'LastName'       => $this->LastName,
            'TimeZone'       => $this->TimeZone,
            'Created'        => $this->Created,
            'Modified'       => $this->Modified
        ];
    }
}
