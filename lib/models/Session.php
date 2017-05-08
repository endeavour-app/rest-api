<?php

namespace Endeavour\Model;

require_once ENDEAVOUR_DIR . 'lib/Model.php';

class Session extends \Endeavour\Model 
{
    protected $UserID;

    protected $Key;

    protected $RemoteAddress;

    protected $Expiry;

    protected $Created;

    protected $Revoked;

    public function isRevoked()
    {
        return $this->Revoked ? true : false;
    }

    public function isExpired()
    {
        $currentDateTime = new \DateTime(
            null,
            new \DateTimeZone('UTC')
        );
        
        $expiryDateTime = clone $this->Created;
        $expiryDateTime->add(new \DateInterval('PT' . $this->Expiry . 'S'));
        
        return $currentDateTime > $expiryDateTime;
    }

    public function isValid()
    {
        return $this->exists() 
            && !$this->isExpired() 
            && !$this->isRevoked();
    }

    public function generateKey()
    {
        if ($this->Key) {
            throw new \Endeavour\Exception('Session key already generated');
        }

        $uniqidPrefix = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_HOST'];
        $this->Key = md5(uniqid( $uniqidPrefix, true));

        return $this;
    }

    public function setUserID($userID) 
    {
        if ($this->UserID) {
            throw new \Endeavour\Exception('User ID already set');
        }

        $this->UserID = $userID;

        return $this;
    }

    public function setExpiry($expiry)
    {
        if ($this->Expiry && $expiry < $this->Expiry) {
            throw new \Endeavour\Exception('Unable to re-set expiry to lesser value', 400);
        }

        $this->Expiry = $expiry;

        return $this;
    }

    public function get($key)
    {
        $value = null;
        
        switch ($key) {
            case 'ID':
                if (!$this->exists()) {
                    throw new \Endeavour\Exception('Unable to get ID, session does not exist');
                }
                $value = $this->ID;
                break;
            case 'UserID':
                $value = $this->UserID;
                break;
            case 'Key':
                $value = $this->Key;
                break;
            case 'RemoteAddress':
                $value = $this->RemoteAddress;
                break;
            case 'Expiry':
                $value = $this->Expiry;
                break;
            case 'Created':
                $value = $this->Created;
                break;
            case 'Revoked':
                $value = $this->Revoked;
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
            case 'UserID':
                $this->setUserID($value);
                break;
            case 'Expiry':
                $this->setExpiry($value);
                break;
            default:
                throw new \Endeavour\Exception("Unable to set $key, $key is not settable");
                break;
        }
        
        return $this;
    }

    public function load() 
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('Unable to load session, no ID or key provided', 400);
        }

        $query = $this->DB->Prepare(
            'SELECT *
            FROM Sessions
            WHERE ID = ?'
        );

        $record = $this->DB->GetRow(
            $query,
            array(
                $this->ID
            ) 
        );

        if (count(array_keys($record)) < 1) {
            throw new \Endeavour\Exception('Unable to load session, database returned empty result');
        }

        return $this->_setProps($record);
    }

    protected function _setProps($props)
    {
        $this->UserID = (int) $props['UserID'];
        $this->Key = $props['Key'];
        $this->Expiry = (int) $props['Expiry'];

        $this->Created = new \DateTime(
            $props['Created'],
            new \DateTimeZone('UTC')
        );

        if (array_key_exists('Revoked', $props) && $props['Revoked']) {
            $this->Revoked = new \DateTime(
                $props['Revoked'],
                new \DateTimeZone('UTC')
            );
        }

        return $this;
    }

    public function save()
    {
        return $this;
    }

    public function create()
    {
        if ($this->exists()) {
            throw new \Endeavour\Exception('Unable to create session, session already exists');
        }

        $query = $this->DB->Prepare(
            'INSERT INTO `Sessions`
            ( `UserID`, `Key`, `RemoteAddress`, `Expiry`, `Created` )
            VALUES
            ( ?, ?, ?, ?, UTC_TIMESTAMP() )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->UserID,
                $this->Key,
                $_SERVER['REMOTE_ADDR'],
                $this->Expiry
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to create session, database error');
        }

        $this->ID = $this->DB->Insert_ID();

        return $this->load();
    }

    public function delete()
    {
        return $this->revoke();
    }

    public function revoke()
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('Session must exist before it can be revoked');
        }

        $query = $this->DB->Prepare(
            'UPDATE Sessions
            SET Revoked = UTC_TIMESTAMP()
            WHERE ID = ?'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->ID
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Session could not be revoked');
        }

        return $this->load();
    }

    public function toArray()
    {
        return [
            'ID'         => $this->ID,
            'UserID'     => $this->UserID,
            'Key'        => $this->Key,
            'Expiry'     => $this->Expiry,
            'Created'    => $this->Created,
            'Revoked'    => $this->isRevoked()
        ];
    }
}
