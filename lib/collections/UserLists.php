<?php

namespace Endeavour\Collection;

require_once ENDEAVOUR_DIR . 'lib/Collection.php';
require_once ENDEAVOUR_DIR . 'lib/models/List.php';

class UserLists extends \Endeavour\Collection
{
    public function setConstraints($constraints)
    {
        $this->constraints = $constraints;
        return $this;
    }

    public function load()
    {
        if (count($this->constraints) < 1) {
            throw new \Endeavour\Exception('Unable to load lists, no constraints set', 500);
        }

        $whereParts = [];

        foreach ($this->constraints as $key => $value) {
            if (!preg_match('/^[A-Z]+$/i', $key)) {
                throw new \Endeavour\Exception('Invalid DB key', 500);
            }
            switch (gettype($value)) {
                case 'boolean':
                    $whereParts[] = "`$key` = " . ($value ? 'TRUE' : 'FALSE');
                    break;
                case 'integer':
                    $whereParts[] = "`$key` = $value";
                    break;
                case 'NULL':
                    $whereParts[] = "`$key` IS NULL";
                    break;
                default:
                    throw new \Endeavour\Exception('Constraint values must be one of: bool, int, or null', 500);
                    break;
            }
        }

        $query = 'SELECT * FROM UserLists WHERE '
            . implode(' AND ', $whereParts);

        $records = $this->DB->GetAll($this->DB->Prepare($query));

        if (count($records) > 0) foreach ($records as $record) {
            $this->add(new \Endeavour\Model\SingleList($record));
        }

        return $this;
    }

    public function add(\Endeavour\DBObject $list)
    {
        $list->loadChildIDs();
        $this->models[] = &$list;
        $this->modelsByID[$list->getID()] = &$list;
        return $this;
    }
}