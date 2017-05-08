<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/interfaces/DBCollection.php';

abstract class Collection implements \Endeavour\DBCollection
{
    protected $models = [];
    protected $modelsByID = [];
    protected $constraints = [];

    protected $DB;

    function __construct($constraints = null)
    {
        $this->DB = &$GLOBALS['DB'];

        if (is_array($constraints)) {
            $this->setConstraints($constraints)
                 ->load();
        }
    }

    abstract public function setConstraints($constraints);

    abstract public function load();

    abstract public function add(\Endeavour\DBObject $model);

    public function has($ID)
    {
        if ($ID < 1) {
            throw new \Endeavour\Exception("Invalid model ID: $ID", 500);
        }
        return array_key_exists($ID, $this->modelsByID);
    }

    public function get($ID)
    {
        if (!$this->has($ID)) {
            throw new \Endeavour\Exception("Model ID: $ID not in collection", 500);
        }
        return $this->modelsByID[$ID];
    }

    public function getAll()
    {
        return $this->models;
    }

    public function getCount()
    {
        return count($this->models);
    }

    public function toArray()
    {
        $items = [];

        foreach ($this->models as &$model) {
            $items[] = $model->toArray();
        }

        return $items;
    }

    public function toJSON()
    {
        $array = $this->toArray();

        $callback = function($item) {
            foreach ($item as $key => $value) {
                if ($value instanceof \DateTime) {
                    $item[$key] = $item[$key]->format(\DateTime::ISO8601);
                }
            }
            return $item;
        };

        array_walk($array, $callback);

        return json_encode($array);
    }
}