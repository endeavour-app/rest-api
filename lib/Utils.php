<?php

namespace Endeavour;

class Utils
{
    public static function hashPassword($password)
    {
        return hash('sha256', $password);
    }
}
