<?php

namespace Endeavour;

class Email
{
    const STATUS_ERROR = -1;
    
    const STATUS_NEW = 0;
    
    const STATUS_READY = 1;
    
    const STATUS_SENDING = 2;
    
    const STATUS_SENT = 3;

    private $ID;
    
    private $TemplateID;

    private $Recipient;
    
    private $Replacements = [];
    
    private $DB;
    
    function __construct()
    {
        $this->DB = &$GLOBALS['DB'];
    
    }
    
    public function getID()
    {
        return $this->ID;
    }

    public function exists()
    {
        return $this->ID ? true : false;
    }
    
    public function useTemplate($templateID)
    {
        $this->TemplateID = $templateID;
        return $this;
    }
    
    public function setRecipient($address, $name = null)
    {
        // Validate address/name
    
        if (!$name) {
            $this->Recipient = $address;
        } else {
            $this->Recipient = sprintf('%1$s <%2$s>', $name, $address);
        }
        
        return $this;
    }
    
    public function setReplacements($assoc)
    {
        foreach ($assoc as $key => $value) {
            $this->setReplacement($key, $value);
        }
        return $this;
    }
    
    public function setReplacement($key, $value)
    {
        $this->Replacements[$key] = $value;
        return $this;
    }
    
    public function send()
    {
        return $this->validate()
                    ->insert()
                    ->insertReplacements()
                    ->ready();
    }
    
    private function validate()
    {
        if (!$this->TemplateID) {
            throw new \Exception('Invalid email, no template specified');
        }
        
        if (!$this->Recipient) {
            throw new \Exception('Invalid email, no recipient specified');
        }
        
        if (!$this->Replacements || count($this->Replacements) < 1) {
            throw new \Exception('Invalid email, no replacements specified');
        }
        
        return $this;
    }
    
    private function insert()
    {
        $query = $this->DB->Prepare(
            'INSERT INTO `Emails`
            ( `TemplateID`, `Recipient`, `Status`, `Created` )
            VALUES
            ( ?, ?, ?, UTC_TIMESTAMP() )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->TemplateID,
                $this->Recipient,
                self::STATUS_NEW
            )
        );

        if (!$result) {
            throw new \Exception('Unable to send email, database error');
        }

        $this->ID = $this->DB->Insert_ID();
        
        return $this;
    }
    
    private function insertReplacements()
    {
        $query = $this->DB->Prepare(
            'INSERT INTO `EmailReplacements`
            ( `EmailID`, `Key`, `Value` )
            VALUES
            ( ?, ?, ? )'
        );
        
        foreach ($this->Replacements as $key => $value) {
            $result = $this->DB->Execute(
                $query,
                array(
                    $this->ID,
                    $key,
                    $value
                )
            );
    
            if (!$result) {
                throw new \Exception('Email replacement could not be set, database error');
            }
        }
        
        return $this;
    }
    
    private function ready()
    {
        if (!$this->exists()) {
            throw new \Exception('Email must exist before it can be set as ready');
        }

        $query = $this->DB->Prepare(
            'UPDATE `Emails`
            SET `Status` = ?
            WHERE `ID` = ?'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                self::STATUS_READY,
                $this->ID
            )
        );

        if (!$result) {
            throw new \Exception('Email staus could not be updated to ready, database error');
        }

        return $this;
    }
}
