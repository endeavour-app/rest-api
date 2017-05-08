<?php

error_reporting(0);
ini_set('display_errors', '0');

// Return OPTIONS faster to reduce XMLHttpRequest overhead
header_remove("X-Powered-By");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type,Accept,Endeavour-Session-Key,Endeavour-Auth-User-ID");
    header("Access-Control-Allow-Methods: POST, GET, PATCH, OPTIONS, DELETE");
    header("HTTP/1.1 200 OK");
    echo '';
    exit;
}

define('ENDEAVOUR_DIR', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

// Require Endeavour configuration
require 'inc/config.dev.php';

// Require Slim vendor library
require 'vendor/slim/slim/Slim/Slim.php';

// Register Slim auto loader
\Slim\Slim::registerAutoloader();

// Endeavour is new Slim app
$endeavour = new \Slim\Slim();

// Endeavour debugging
$endeavour->config('debug', false);

// Require ADOdb
require 'vendor/adodb/adodb-php/adodb.inc.php';

// Database connection
$DB = NewADOConnection('mysqli');
$DB->Connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

// Require Endeavour Gatekeeper
require 'lib/Gatekeeper.php';

// Initialize Gatekeeper
$endeavour->container->singleton('Gatekeeper', function () {
    return new \Endeavour\Gatekeeper();
});

$endeavour->error(function (\Exception $e) use ($endeavour) {

    $endeavour->response->setStatus($e->getResponseCode());

    $DB = $GLOBALS['DB'];

    $finishMicrotime = microtime(true);
    $duration = $finishMicrotime - $_SERVER['REQUEST_TIME_FLOAT'];
    $isAuthenticated = $endeavour->Gatekeeper->isAuthed();

    $query = $DB->Prepare(
        "INSERT INTO `Logs`
        (`Method`, `Route`, `HostName`, `SSL`, `RemoteAddress`, `UserAgent`, `Authenticated`, `SessionID`, `ResponseCode`, `Error`, `Duration`, `Date`)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())"
    );

    $DB->Execute(
        $query,
        [
            $endeavour->request->getMethod(),
            $_SERVER['REQUEST_URI'],
            $endeavour->request->getHost(),
            $endeavour->request->getScheme() == 'https' ? true : false,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $isAuthenticated,
            $isAuthenticated ? $endeavour->Gatekeeper->SessionID : null,
            $endeavour->response->getStatus(),
            $e->toJSON(),
            $duration,
        ]
    );

    $e->printJSON();

});

$endeavour->hook('slim.after', function () use ($endeavour) {

    $DB = $GLOBALS['DB'];

    $finishMicrotime = microtime(true);
    $duration = $finishMicrotime - $_SERVER['REQUEST_TIME_FLOAT'];
    $isAuthenticated = $endeavour->Gatekeeper->isAuthed();

    $query = $DB->Prepare(
        "INSERT INTO `Logs`
        (`Method`, `Route`, `HostName`, `SSL`, `RemoteAddress`, `UserAgent`, `Authenticated`, `SessionID`, `ResponseCode`, `Error`, `Duration`, `Date`)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())"
    );

    $DB->Execute(
        $query,
        [
            $endeavour->request->getMethod(),
            $_SERVER['REQUEST_URI'],
            $endeavour->request->getHost(),
            $endeavour->request->getScheme() == 'https' ? true : false,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $isAuthenticated,
            $isAuthenticated ? $endeavour->Gatekeeper->SessionID : null,
            $endeavour->response->getStatus(),
            $endeavour->response->getStatus() >= 400 ? $app->response->getBody() : null,
            $duration,
        ]
    );

});

// Load routes
require 'routes/all.php';

// Set headers
$endeavour->response->headers->set('Content-Type',                       'application/json');
$endeavour->response->headers->set('Access-Control-Allow-Origin',        '*');
$endeavour->response->headers->set('Access-Control-Allow-Headers',       'Content-Type,Accept,Endeavour-Session-Key,Endeavour-Auth-User-ID');
$endeavour->response->headers->set('Access-Control-Allow-Methods',       'POST, GET, PATCH, OPTIONS, DELETE');
$endeavour->response->headers->set('Endeavour-API-Version',              '0.1.0');

// Run Endeavour
$endeavour->run();
