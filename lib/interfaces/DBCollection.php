<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/interfaces/ResponseObject.php';

interface DBCollection extends ResponseObject 
{
    public function has($ID);

    public function get($ID);

    public function load();
}