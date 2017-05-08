<?php

namespace Endeavour\Model;

require_once ENDEAVOUR_DIR . 'lib/Model.php';

class ListItem extends \Endeavour\Model
{
    protected $ListID;

    protected $UserID;

    protected $Summary;

    protected $Created;

    protected $Completed = null;

    protected $Due = null;
    
    protected $Deleted = null;

    public function isDeleted()
    {
        return $this->Deleted ? true : false;
    }

    public function isCompleted()
    {
        return $this->Completed ? true : false;
    }
    
    public function get($key)
    {
        $value = null;
        
        switch ($key) {
            case 'ID':
                if (!$this->exists()) {
                    throw new \Endeavour\Exception('Unable to get ID, list item does not exist');
                }
                $value = $this->ID;
                break;
            case 'ListID':
                $value = $this->ListID;
                break;
            case 'UserID':
                $value = $this->UserID;
                break;
            case 'Summary':
                $value = $this->Summary;
                break;
            case 'Created':
                $value = $this->Created;
                break;
            case 'Completed':
                $value = $this->Completed;
                break;
            case 'Due':
                $value = $this->Due;
                break;
            case 'Deleted':
                $value = $this->Deleted;
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
            case 'ListID':
                // if ($this->exists()) {
                //     throw new \Endeavour\Exception('Cannot move list items yet!');
                //     $this->moveTo($value);
                // }
                $this->ListID = $value;
                break;
            case 'UserID':
                if ($this->exists()) {
                    throw new \Endeavour\Exception('Cannot change User ID of a list item');
                }
                $this->UserID = $value;
                break;
            case 'Summary':
                $this->Summary = $value;
                break;
            case 'Completed':
                if ($value instanceof \DateTime) {
                    $this->Completed = $value;
                } elseif ($value && strtolower($value) == 'now') {
                    $db_utc_timestamp = $this->DB->getOne("SELECT UTC_TIMESTAMP()");
                    $this->Completed = new \DateTime($value, new \DateTimeZone('UTC'));
                } else {
                    $this->Completed = $value ? new \DateTime($value, new \DateTimeZone('UTC')) : null;
                }
                break;
            case 'Due':
                if ($value instanceof \DateTime) {
                    $this->Due = $value;
                } else {
                    $this->Due = $value ? new \DateTime($value, new \DateTimeZone('UTC')) : null;
                }
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
            throw new \Endeavour\Exception('Unable to load list item, no ID provided', 400);
        }

        $query = $this->DB->Prepare(
            'SELECT *
            FROM ListItems
            WHERE ID = ?'
        );

        $record = $this->DB->GetRow(
            $query,
            array(
                $this->ID
            ) 
        );

        if (count(array_keys($record)) < 1) {
            throw new \Endeavour\Exception('Unable to load list item, database returned empty result', 400);
        }

        return $this->_setProps($record);
    }

    protected function _setProps($props)
    {
        $this->ListID = (int) $props['ListID'];
        $this->UserID = (int) $props['UserID'];
        $this->Summary = $props['Summary'];

        $this->Created = new \DateTime(
            $props['Created'],
            new \DateTimeZone( 'UTC' )
        );

        if (array_key_exists('Completed', $props) && $props['Completed']) {
            $this->Completed = new \DateTime(
                $props['Completed'],
                new \DateTimeZone( 'UTC' )
            );
        }

        if (array_key_exists('Due', $props) && $props['Due']) {
            $this->Due = new \DateTime(
                $props['Due'],
                new \DateTimeZone( 'UTC' )
            );
        }

        if (array_key_exists('Deleted', $props) && $props['Deleted']) {
            $this->Deleted = new \DateTime(
                $props['Deleted'],
                new \DateTimeZone( 'UTC' )
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
            "UPDATE `ListItems`
            SET $set
            WHERE `ID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            $replacements
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to save list item, database error');
        }

        $this->load()
             ->_clearChangedVars();
        
        return $this;
    }

    public function create()
    {
        if ($this->exists()) {
            throw new \Endeavour\Exception('Unable to create list item, list item already exists');
        }

        $query = $this->DB->Prepare(
            'INSERT INTO `ListItems`
            ( `ListID`, `UserID`, `Summary`, `Created`, `Due` )
            VALUES
            ( ?, ?, ?, UTC_TIMESTAMP(), ? )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->ListID,
                $this->UserID,
                $this->Summary,
                $this->Due ? $this->Due->format('Y-m-d H:i:s') : null,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to create list item, database error');
        }

        $this->ID = $this->DB->Insert_ID();

        return $this->load();
    }

    public function delete()
    {
        if (!$this->exists() && !$this->Deleted) {
            throw new \Endeavour\Exception('List does not exist or was already deleted!');
        }

        $query = $this->DB->Prepare(
            "UPDATE `ListItems`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            [$this->ID]
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to delete list item, database error');
        }

        return $this;
    }

    public function moveTo($ListID)
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('Cannot move a list item before it is created');
        }

        if ($ListID == $this->ListID) {
            return $this;
        }

        return $this;
    }

    public function toArray()
    {
        return [
            'ID'         => $this->ID,
            'ListID'     => $this->ListID,
            'UserID'     => $this->UserID,
            'Summary'    => $this->Summary,
            'Created'    => $this->Created,
            'Completed'  => $this->Completed,
            'Due'        => $this->Due,
            'Deleted'    => $this->isDeleted(),
        ];
    }
}
