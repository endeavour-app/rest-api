<?php

namespace Endeavour;

require_once ENDEAVOUR_DIR . 'lib/Exception.php';

class Gatekeeper
{
    public $UserID;
    public $SessionKey;
    public $SessionID;

    public $Session;

    private $app;
    private $DB;

    function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
        $this->DB = &$GLOBALS['DB'];

        $this->UserID = $this->app->request->headers->get('Endeavour-Auth-User-ID');
        $this->SessionKey = $this->app->request->headers->get('Endeavour-Session-Key');

        if ($this->UserID && $this->SessionKey) {
            $this->loadSession();
        }
    }

    private function loadSession()
    {
        $query = $this->DB->prepare(
            "SELECT `ID`
            FROM `Sessions`
            WHERE `UserID` = ?
            AND `Key` = ?"
        );

        $this->SessionID = $this->DB->GetOne(
            $query,
            array(
                $this->UserID,
                $this->SessionKey
            )
        );

        if ($this->SessionID) {
            return $this->setupSessionModel();
        }

        return $this;
    }

    private function setupSessionModel()
    {
        $this->Session = new Model\Session($this->SessionID);
        return $this;
    }

    public function isAuthed()
    {
        return $this->SessionID && $this->Session->isValid();
    }

    public function assertValidSession()
    {
        if (!$this->isAuthed()) {
            throw new \Endeavour\Exception('Invalid session', '400::invalid_session');
        }

        return $this;
    }

    public function assertCurrentUserCan(\Slim\Route $route)
    {



        return;
    }
}
