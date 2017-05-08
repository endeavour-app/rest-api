<?php

namespace Endeavour\Model;

require_once ENDEAVOUR_DIR . 'lib/Model.php';
require_once ENDEAVOUR_DIR . 'lib/models/User.php';

class SingleList extends \Endeavour\Model
{
    protected $ParentID = null;

    protected $UserID;

    protected $OwnerID;

    protected $Shared;

    protected $Title;

    protected $Description = null;

    protected $Created;

    protected $Start = null;

    protected $Due = null;
    
    protected $Deleted = null;

    protected $childIDs = [];

    public function isDeleted()
    {
        return $this->Deleted ? true : false;
    }
    
    public function get( $key )
    {
        $value = null;
        
        switch ($key) {
            case 'ID':
                if (!$this->exists()) {
                    throw new \Endeavour\Exception('Unable to get ID, list does not exist');
                }
                $value = $this->ID;
                break;
            case 'ParentID':
                $value = $this->ParentID;
                break;
            case 'UserID':
                $value = $this->UserID;
                break;
            case 'Shared':
                $value = (bool) $this->Shared;
                break;
            case 'Title':
                $value = $this->Title;
                break;
            case 'Description':
                $value = $this->Description;
                break;
            case 'Created':
                $value = $this->Created;
                break;
            case 'Start':
                $value = $this->Start;
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

    public function set( $key, $value )
    {
        switch ($key) {
            case 'ParentID':
                // if ($this->exists()) {
                //     throw new \Endeavour\Exception('Cannot move lists yet!', 500);
                //     // $this->moveTo($value);
                // }
                $this->ParentID = $value;
                break;
            case 'UserID':
                if ($this->exists()) {
                    throw new \Endeavour\Exception('Cannot change list owner', 500);
                }
                $this->UserID = $value;
                break;
            case 'Title':
                $this->Title = $value;
                break;
            case 'Description':
                $this->Description = $value;
                break;
            case 'Start':
                if ($value instanceof \DateTime) {
                    $this->Start = $value;
                } else {
                    $this->Start = $value ? new \DateTime($value, new \DateTimeZone('UTC')) : null;
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

    public function getChildIDs()
    {
        return $this->childIDs;
    }

    public function countChildIDs()
    {
        return is_array($this->childIDs) ? count($this->childIDs) : 0;
    }

    public function userPermitted($UserID)
    {
        if ($this->UserID == $UserID) {
            return true;
        }
        
        $query = $this->DB->Prepare(
            'SELECT ID
            FROM UserLists
            WHERE ID = ?
            AND UserID = ?
            AND Deleted IS NULL'
        );

        $ID = $this->DB->GetCol(
            $query,
            array(
                $this->ID,
                $UserID
            ) 
        );

        if ($ID) return true;

        $ancestors = $this->getAncestors();
        $ancestorIDs = array_keys($ancestors);
        
        $query = $this->DB->Prepare(
            'SELECT ID
            FROM UserLists
            WHERE UserID = ?
            AND Deleted IS NULL'
        );

        $sharedListIDs = $this->DB->GetCol(
            $query,
            array(
                $UserID
            )
        );

        if (count(array_values(array_intersect($ancestorIDs, $sharedListIDs))) > 0) return true;

        return false;
    }

    public function getAncestors()
    {
        if (!$this->ParentID) return null;

        $ancestorIDs = $this->getAncestorIDs();

        if (!$ancestorIDs) return null;

        $ancestors = new Collection\Lists([
            'ListID' => $ancestorIDs,
        ]);

        return $ancestors;
    }

    public function getAncestorIDs()
    {
        $query = $this->DB->Prepare(
            'SELECT GetListAncestry(ID) AS `AncestorIDs`
            FROM Lists
            WHERE ID = ?'
        );

        $ancestorIDs = $this->DB->GetCol(
            $query,
            [$this->ID]
        );

        if (!$ancestorIDs || !$ancestorIDs[0]) return null;

        $ancestorIDs = array_map('intval', explode(',', $ancestorIDs[0]));

        return $ancestorIDs;
    }

    public function getDescendants($inclDeleted = false)
    {
        $descendantIDs = $this->getDescendantIDs();

        if (!$descendantIDs) return null;

        $collectionOptions = [
            'ListID' => $descendantIDs,
        ];

        if (!$inclDeleted) {
            $collectionOptions['Deleted'] = null;
        }

        $descendants = new Collection\Lists($collectionOptions);

        return $descendants;
    }

    public function getDescendantIDs()
    {
        $query = $this->DB->Prepare(
            'SELECT GetListDescendants(ID) AS `DescendantIDs`
            FROM Lists
            WHERE ID = ?'
        );

        $descendantIDs = $this->DB->GetCol(
            $query,
            [$this->ID]
        );

        if (!$descendantIDs || !$descendantIDs[0]) return null;

        $descendantIDs = array_map('intval', explode(',', $descendantIDs[0]));

        return $descendantIDs;
    }

    public function loadChildIDs()
    {
        if (!$this->exists()) {
            return $this;
        }

        $query = $this->DB->Prepare(
            'SELECT ID
            FROM Lists
            WHERE ParentID = ?
            AND Deleted IS NULL'
        );

        $IDs = $this->DB->GetCol(
            $query,
            array(
                $this->ID
            ) 
        );

        if (is_array($IDs) && count($IDs) > 0) {
            $this->childIDs = $IDs;
        }

        return $this;
    }

    public function load() 
    {
        if (!$this->exists()) {
            throw new \Endeavour\Exception('Unable to load list, no ID or key provided', 400);
        }

        $query = $this->DB->Prepare(
            'SELECT L.*, 
                if(
                    (select `ListShares`.`ID` 
                        from `ListShares` 
                        where ((`ListShares`.`ListID` = `L`.`ID`) 
                            and isnull(`ListShares`.`Deleted`) 
                            and (isnull(`ListShares`.`Thru`) 
                                or (`ListShares`.`Thru` > utc_timestamp())) 
                            and (isnull(`ListShares`.`From`) 
                                or (`ListShares`.`From` < utc_timestamp()))) 
                        limit 1),
                    1,
                    0
                ) AS `Shared`
            FROM Lists L
            WHERE L.ID = ?'
        );

        $record = $this->DB->GetRow(
            $query,
            array(
                $this->ID
            ) 
        );

        if (count(array_keys($record)) < 1) {
            throw new \Endeavour\Exception('Unable to load list, database returned empty result');
        }

        // Load children
        $this->loadChildIDs();

        return $this->_setProps($record);
    }

    protected function _setProps($props)
    {
        $this->ParentID = $props['ParentID'];
        $this->UserID = (int) $props['UserID'];
        $this->OwnerID = array_key_exists('OwnerID', $props) ? (int) $props['OwnerID'] : $this->UserID;
        $this->Shared = (bool) $props['Shared'];
        $this->Title = $props['Title'];
        $this->Description = $props['Description'];

        $this->Created = new \DateTime(
            $props['Created'],
            new \DateTimeZone( 'UTC' )
        );

        if (array_key_exists('Start', $props) && $props['Start']) {
            $this->Start = new \DateTime(
                $props['Start'],
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
            "UPDATE `Lists`
            SET $set
            WHERE `ID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            $replacements
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to save list, database error');
        }

        $this->load()
             ->_clearChangedVars();
        
        return $this;
    }

    public function create()
    {
        if ($this->exists()) {
            throw new \Endeavour\Exception('Unable to create list, list already exists');
        }

        $query = $this->DB->Prepare(
            'INSERT INTO `Lists`
            ( `ParentID`, `UserID`, `Title`, `Description`, `Created`, `Start`, `Due` )
            VALUES
            ( ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ? )'
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->ParentID,
                $this->UserID,
                $this->Title,
                $this->Description,
                $this->Start,
                $this->Due,
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to create list, database error');
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
            "UPDATE `Lists`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ID` = ?
            LIMIT 1"
        );

        $result = $this->DB->Execute(
            $query,
            [$this->ID]
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to delete list, database error');
        }

        // Delete all items
        $this->deleteAllItems();

        // Delete all descendants
        $this->deleteAllDescendants();

        return $this;
    }

    public function deleteAllDescendants()
    {
        $descendantIDs = $this->getDescendantIDs();

        if (!$descendantIDs) return $this;

        $query = $this->DB->Prepare(
            "UPDATE `Lists`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ID` IN(" . implode(",", $descendantIDs) . ")
            AND `Deleted` IS NULL"
        );

        $result = $this->DB->Execute($query);

        if (!$result) {
            throw new \Endeavour\Exception('Unable to delete list descendants, database error: ' . $this->DB->ErrorMsg());
        }

        $query = $this->DB->Prepare(
            "UPDATE `ListItems`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ListID` IN(" . implode(",", $descendantIDs) . ")
            AND `Deleted` IS NULL"
        );

        $result = $this->DB->Execute($query);

        if (!$result) {
            throw new \Endeavour\Exception('Unable to delete list descendants items, database error: ' . $this->DB->ErrorMsg());
        }

        return $this;
    }

    public function deleteAllItems()
    {
        $query = $this->DB->Prepare(
            "UPDATE `ListItems`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ListID` = ?
            AND `Deleted` IS NULL"
        );

        $result = $this->DB->Execute(
            $query,
            [$this->ID]
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to delete list items, database error');
        }

        return $this;
    }

    public function shareWith($UserID)
    {
        if ($this->userPermitted($UserID)) {
            return $this;
        }

        $this->addUser($UserID);

        return $this;
    }

    public function addUser($UserID)
    {
        $query = $this->DB->Prepare(
            "INSERT INTO `ListShares`
            ( `ListID`, `UserID`, `RoleID`, `Created` )
            VALUES
            ( ?, ?, 1, UTC_TIMESTAMP() )"
        );

        $result = $this->DB->Execute(
            $query,
            [$this->ID, $UserID]
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to add user to list, database error');
        }

        return $this;
    }

    public function removeUser($UserID)
    {
        $query = $this->DB->Prepare(
            "SELECT `ID` 
            FROM `ListShares` 
            WHERE `ListID` = ?
            AND `UserID` = ?
            AND `Deleted` IS NULL
            AND (`Thru` IS NULL OR `Thru` > UTC_TIMESTAMP())
            AND (`From` IS NULL OR `From` < UTC_TIMESTAMP())
            LIMIT 1"
        );

        $ListShareID = $this->DB->GetCol(
            $query,
            [$this->ID, $UserID]
        );

        $query = $this->DB->Prepare(
            "UPDATE `ListShares` 
            SET `Deleted` = UTC_TIMESTAMP() 
            WHERE `ID` = ?"
        );

        $result = $this->DB->Execute(
            $query,
            [$ListShareID]
        );

        if (!$result) {
            throw new \Endeavour\Exception('Unable to add user to list, database error');
        }

        return $this;
    }

    public function toArray()
    {
        $array = [
            'ID'            => $this->ID,
            'ParentID'      => $this->ParentID,
            'UserID'        => $this->UserID,
            'OwnerID'       => $this->OwnerID,
            'Shared'        => $this->Shared,
            'Title'         => $this->Title,
            'Description'   => $this->Description,
            'Lists'         => $this->countChildIDs(),
            'Created'       => $this->Created,
            'Start'         => $this->Start,
            'Due'           => $this->Due,
            'Deleted'       => $this->isDeleted(),
        ];

        // Return owner user object if OwnerID != UserID
        if ($this->UserID !== $this->OwnerID) {
            $owner = new User($this->OwnerID);
            $array['Owner'] = $owner->toArray();
        }

        return $array;
    }

    public function extendedToJSON()
    {
        $array = $this->prepareJSONVars($this->toArray());

        $array['AncestorIDs'] = $this->getAncestorIDs();
        $array['DescendantIDs'] = $this->getDescendantIDs();

        return json_encode($array);
    }
}
