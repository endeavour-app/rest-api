<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/interfaces/ResponseObject.php';

abstract class Error implements ResponseObject 
{

    abstract public function toArray();

    public function getDescription() 
    {
        return $this->description;
    }

    public function getResponseCode() 
    {
        return static::$responseCode;
    }

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

}