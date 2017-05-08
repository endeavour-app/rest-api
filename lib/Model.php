<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/interfaces/DBObject.php';

abstract class Model implements \Endeavour\DBObject 
{
    protected $ID;

    protected $changedVars = [];

    protected $DB;
    
    function __construct($input = null)
    {
        $this->DB = &$GLOBALS['DB'];
        
        if ($input) $this->_parseInput($input);
    }

    private function _parseInput($input)
    {
        if (is_array($input)) {
            if (array_key_exists('ID', $input)) $this->ID = (int) $input['ID'];
            $this->_setProps($input);
        } else {
            $this->_setID($input);
            $this->load();
        }
    }

    protected function _setID($ID) {
        $this->ID = (int) $ID;
        return $this;
    }

    abstract protected function _setProps($props);

    abstract public function get($key);

    abstract public function set($key, $value);

    abstract public function load();

    abstract public function create();

    abstract public function save();

    abstract public function toArray();

    public function getID()
    {
        return $this->ID;
    }

    public function exists()
    {
        return $this->ID ? true : false;
    }

    public function delete()
    {
        $modelName = $this->getModelName();

        if (!$this->exists()) {
            throw new \Endeavour\Exception("$modelName must exist before it can be deleted", 400);
        }

        $query = $this->DB->Prepare(
            "UPDATE `$modelName`
            SET `Deleted` = UTC_TIMESTAMP()
            WHERE `ID` = ?"
        );

        $result = $this->DB->Execute(
            $query,
            array(
                $this->ID
            )
        );

        if (!$result) {
            throw new \Endeavour\Exception("$modelName could not be deleted, database error");
        }

        return $this;
    }

    protected function prepareJSONVars($array)
    {
        foreach ($array as $key => $value) {
            if ($value instanceof \DateTime) {
                $array[$key] = $array[$key]->format(\DateTime::ISO8601);
            }
        }

        return $array;
    }

    public function toJSON()
    {
        $array = $this->prepareJSONVars($this->toArray());

        return json_encode($array);
    }
    
    public function setMultiple($array)
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    public function getModelName()
    {
        return join('', array_slice(explode('\\', __CLASS__), -1));
    }

    protected function _getChangedVars()
    {
        return $this->changedVars;
    }

    protected function _clearChangedVars()
    {
        $this->changedVars = [];
        return $this;
    }
}