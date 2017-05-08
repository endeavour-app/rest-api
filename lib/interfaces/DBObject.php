<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/interfaces/ResponseObject.php';

interface DBObject extends ResponseObject 
{
    public function exists();

    public function get($key);

    public function set($key, $value);

    public function load();

    public function create();

    public function save();

    public function delete();
}