<?php

namespace Endeavour\Collection;

require_once ENDEAVOUR_DIR . 'lib/Collection.php';
require_once ENDEAVOUR_DIR . 'lib/models/List.php';

class Lists extends \Endeavour\Collection
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
                case 'array':
                    $whereParts[] = "FIND_IN_SET(`$key`, " . $this->DB->qstr(implode(',', $value)) . ')';
                    break;
                case 'integer':
                    $whereParts[] = "`$key` = $value";
                    break;
                case 'NULL':
                    $whereParts[] = "`$key` IS NULL";
                    break;
                default:
                    throw new \Endeavour\Exception('Constraint values must be one of: bool, int, array, or null', 500);
                    break;
            }
        }

        $query = 'SELECT L.*, 
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
            WHERE '
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