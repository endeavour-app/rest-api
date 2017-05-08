<?php

namespace Endeavour;

interface ResponseObject 
{
    public function toArray();

    public function toJSON();
}