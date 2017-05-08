<?php

namespace Endeavour\Model;

require_once ENDEAVOUR_DIR . 'lib/Model.php';

class ListItemDetails extends \Endeavour\Model
{
    protected $ListItemID;

    protected $Body = '';
    
    protected $Modified;
    
    protected function _setID($ID)
    {
        parent::_setID($ID);
        $this->ListItemID = $ID;
        return $this;
    }
    
    public function get($key)
    {
        $value = null;
        
        switch ($key) {
            case 'ListItemID':
                $value = $this->ListItemID;
                break;
            case 'Body':
                $value = $this->Body;
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
            case 'ListItemID':
                $this->ListItemID = $value;
                break;
            case 'Body':
                $this->Body = $value;
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
            throw new \Endeavour\Exception('Unable to load list item details, no ListItemID provided', 400);
        }

        $query = $this->DB->Prepare(
            'SELECT *
            FROM `ListItemTexts`
            WHERE `ListItemID` = ?'
        );

        $record = $this->DB->GetRow(
            $query,
            [$this->ListItemID]
        );

        if (!$record || count(array_keys($record)) < 1) {
            // Create the record if it doesn't exist
            $this->ID = null;
            return $this->create();
        }

        return $this->_setProps($record);
    }

    protected function _setProps($props)
    {
        $this->Body = $props['Body'];

        $this->Modified = new \DateTime(
            $props['Modified'],
            new \DateTimeZone( 'UTC' )
        );

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
        $replacements = [$this->ListItemID];

        foreach ($changedVars as $key) {

            $value = $this->{$key};
            $setSQL = "`$key` = ?";

            if ($value instanceof \DateTime) $value = $value->format('Y-m-d H:i:s');

            array_unshift($set, $setSQL);
            array_unshift($replacements, $value);

        }

        $set = implode(', ', $set);

        $query = $this->DB->Prepare(
            "UPDATE `ListItemTexts`
            SET $set
            WHERE `ListItemID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            $replacements
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to save list item details, database error');
        }

        $this->load()
             ->_clearChangedVars();
        
        return $this;
    }

    public function create()
    {
        if ($this->exists()) {
            throw new \Endeavour\Exception('Unable to create list item details, already exists');
        }

        $query = $this->DB->Prepare(
            'INSERT INTO `ListItemTexts`
            ( `ListItemID`, `Body`, `Modified` )
            VALUES
            ( ?, ?, UTC_TIMESTAMP() )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->ListItemID,
                $this->Body,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to create list item details, database error');
        }

        $this->ID = $this->ListItemID;

        return $this->load();
    }

    public function delete()
    {
        return $this;
    }

    public function toArray()
    {
        return [
            'ListItemID' => $this->ListItemID,
            'Body'       => $this->Body,
            'Modified'   => $this->Modified,
        ];
    }
}
