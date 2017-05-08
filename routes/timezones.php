<?php

namespace Endeavour;

// GET route
$endeavour->get(
    '/timezones',
    function () use ($endeavour) {

        // Return TimeZones array
        echo json_encode(\DateTimeZone::listIdentifiers());

    }
);