<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/List.php';
require_once ENDEAVOUR_DIR . 'lib/models/ListItem.php';
require_once ENDEAVOUR_DIR . 'lib/models/ListItemDetails.php';

// GET route
$endeavour->get(
    '/listitems/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get ListItem
        $item = new Model\ListItem($ID);
        
        // Current user can access list this item belongs to?
        $list = new Model\SingleList($item->get('ListID'));
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        // Return ListItem
        echo $item->toJSON();

    }
);

// GET route
$endeavour->get(
    '/listitems/:ID/details',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get ListItem
        $item = new Model\ListItem($ID);
        
        // Current user can access list this item belongs to?
        $list = new Model\SingleList($item->get('ListID'));
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        // Get ListItemDetails
        $itemDetails = new Model\ListItemDetails($item->get('ID'));

        // Return ListItem
        echo $itemDetails->toJSON();

    }
);

// POST route
$endeavour->post(
    '/listitems',
    function () use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Create ListItem
        $item = new Model\ListItem();

        $request = json_decode($endeavour->request()->getBody(), true);

        if (!array_key_exists('ListID', $request) || !trim($request['ListID'])) {
            throw new Exception('ListID must be specified', '400::fields_missing');
        }

        if (!array_key_exists('Summary', $request) || !trim($request['Summary'])) {
            throw new Exception('Summary must be specified', '400::fields_missing');
        }

        // Current user can access list this item is going to end up in?
        $list = new Model\SingleList($request['ListID']);
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        $attrs = [
            'UserID' => $endeavour->Gatekeeper->Session->get('UserID'),
            'ListID' => $request['ListID'],
            'Summary' => $request['Summary'],
        ];

        if (array_key_exists('Due', $request)) {
            $attrs['Due'] = $request['Due'];
        }

        $item->setMultiple($attrs)
             ->create();

        // Set Details
        $detailsAttrs = [
            'ListItemID' => $item->get('ID'),
        ];

        if (array_key_exists('Details', $request)) {
            $detailsAttrs['Body'] = $request['Details'];
        }

        $itemDetails = new Model\ListItemDetails();
        $itemDetails->setMultiple($detailsAttrs)
                    ->create();

        // Return ListItem
        echo $item->toJSON();

    }
);

// PATCH route
$endeavour->patch(
    '/listitems/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get ListItem
        $item = new Model\ListItem($ID);
        
        // Current user can access list this item belongs to?
        $list = new Model\SingleList($item->get('ListID'));
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        $request = json_decode($endeavour->request()->getBody(), true);

        $attrs = [];

        if (array_key_exists('ListID', $request)) {
            $attrs['ListID'] = $request['ListID'];
        }

        if (array_key_exists('Completed', $request)) {
            $attrs['Completed'] = $request['Completed'];
        }

        if (array_key_exists('Due', $request)) {
            $attrs['Due'] = $request['Due'];
        }

        if (array_key_exists('Summary', $request)) {
            $attrs['Summary'] = $request['Summary'];
        }

        if (array_key_exists('Details', $request)) {
            $attrs['Details'] = $request['Details'];
        }

        if (count($attrs) > 0 ) {
            $item->setMultiple($attrs)
                 ->save();
        }

        // Return ListItem
        echo $item->toJSON();

    }
);

// PATCH route
$endeavour->patch(
    '/listitems/:ID/details',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get ListItem
        $item = new Model\ListItem($ID);

        // Current user can access list this item belongs to?
        $list = new Model\SingleList($item->get('ListID'));
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        // Get ListItemDetails
        $itemDetails = new Model\ListItemDetails($item->get('ID'));

        $request = json_decode($endeavour->request()->getBody(), true);

        $attrs = [];

        if (array_key_exists('Body', $request)) {
            $attrs['Body'] = $request['Body'];
        }

        if (count($attrs) > 0 ) {
            $itemDetails->setMultiple($attrs)
                        ->save();
        }

        // Return ListItem
        echo $itemDetails->toJSON();

    }
);

// DELETE route
$endeavour->delete(
    '/listitems/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get ListItem
        $item = new Model\ListItem($ID);

        // Current user can access list this item belongs to?
        $list = new Model\SingleList($item->get('ListID'));
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        $item->delete();

        // Return Empty
        echo json_encode([]);

    }
);

// GET route (searching)
$endeavour->get(
    '/listitems',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        


        echo json_encode([]);

    }
);