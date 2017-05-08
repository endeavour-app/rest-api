<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/models/List.php';
require_once ENDEAVOUR_DIR . 'lib/collections/Lists.php';
require_once ENDEAVOUR_DIR . 'lib/collections/UserLists.php';
require_once ENDEAVOUR_DIR . 'lib/collections/ListItems.php';

// GET route
$endeavour->get(
    '/lists',
    function () use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get all lists for a user
        $collection = new Collection\UserLists([
            'UserID' => $endeavour->Gatekeeper->Session->get('UserID'),
            'ParentID' => null,
            'Deleted' => null,
        ]);

        // Return ListItem
        echo $collection->toJSON();

    }
);

// GET route
$endeavour->get(
    '/lists/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get List
        $list = new Model\SingleList($ID);
        
        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        // Return List
        echo $list->extendedToJSON();

    }
);

// GET route
$endeavour->get(
    '/lists/:ListID/items',
    function ($ListID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $list = new Model\SingleList($ListID);

        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }
        
        // Get all items in a list
        $collection = new Collection\ListItems([
            'ListID' => $list->get('ID'),
            'Deleted' => null,
        ]);

        // Return ListItem
        echo $collection->toJSON();

    }
);

// GET route
$endeavour->get(
    '/lists/:ListID/lists',
    function ($ListID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();

        $list = new Model\SingleList($ListID);

        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }
        
        // Get all items in a list
        $collection = new Collection\Lists([
            'ParentID' => $list->get('ID'),
            'Deleted' => null,
        ]);

        // Return ListItem
        echo $collection->toJSON();

    }
);

// POST route
$endeavour->post(
    '/lists',
    function () use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Create List
        $item = new Model\SingleList();

        $request = json_decode($endeavour->request()->getBody(), true);

        if (!array_key_exists('Title', $request) || !trim($request['Title'])) {
            throw new Exception('Title must be specified', '400::fields_missing');
        }

        $attrs = [
            'UserID' => $endeavour->Gatekeeper->Session->get('UserID'),
            'Title' => $request['Title'],
        ];

        if (array_key_exists('ParentID', $request) && $request['ParentID']) {
            //TODO: Get ParentID list and see if they are allowed to nest here
            $attrs['ParentID'] = $request['ParentID'];
        }

        if (array_key_exists('Description', $request) && $request['Description']) {
            $attrs['Description'] = $request['Description'];
        }

        if (array_key_exists('Start', $request) && $request['Start']) {
            $attrs['Start'] = $request['Start'];
        }

        if (array_key_exists('Due', $request) && $request['Due']) {
            $attrs['Due'] = $request['Due'];
        }

        $item->setMultiple($attrs)
             ->create();

        // Return ListItem
        echo $item->toJSON();

    }
);

// PATCH route
$endeavour->patch(
    '/lists/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get List
        $list = new Model\SingleList($ID);

        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }
        
        if ($list->get('UserID') != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $request = json_decode($endeavour->request()->getBody(), true);

        $attrs = [];

        if (array_key_exists('ParentID', $request)) {
            $attrs['ParentID'] = $request['ParentID'];
        }

        if (array_key_exists('Title', $request)) {
            $attrs['Title'] = $request['Title'];
        }

        if (count($attrs) > 0 ) {
            $list->setMultiple($attrs)
                 ->save();
        }

        // Return List
        echo $list->toJSON();

    }
);

// DELETE route
$endeavour->delete(
    '/lists/:ID',
    function ($ID) use ($endeavour) {

        $endeavour->Gatekeeper->assertValidSession();
        
        // Get List
        $list = new Model\SingleList($ID);

        if (!$list->userPermitted($endeavour->Gatekeeper->Session->get('UserID'))) {
            $endeavour->accessDenied();
        }

        // if ($list->userIsOwner($endeavour->Gatekeeper->Session->get('UserID'))) {
        //     $list->delete();
        // }
        // else {
        //     $list->removeSharingForUser();
        // }
        
        if ($list->get('UserID') != $endeavour->Gatekeeper->Session->get('UserID')) {
            $endeavour->accessDenied();
        }

        $list->delete();

        // Return Empty
        echo json_encode([]);

    }
);