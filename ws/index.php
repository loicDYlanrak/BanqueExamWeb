<?php
    require 'vendor/autoload.php';
    require 'db.php';
    require 'controllers/ClientController.php';
    require 'controllers/FondController.php';

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    Flight::route('OPTIONS /*', function() {
        Flight::json([], 200);
    });

    // Routes pour les fonds
    Flight::route('POST /fonds', ['FondController', 'create']);
    Flight::route('GET /fonds', ['FondController', 'getAll']);
    Flight::route('GET /fonds/solde', ['FondController', 'getSolde']);

    // Routes pour les clients
    Flight::route('GET /clients', ['ClientController', 'getAll']);
    Flight::route('GET /clients/@id', ['ClientController', 'getById']);
    Flight::route('POST /clients', ['ClientController', 'create']);
    Flight::route('PUT /clients/@id', ['ClientController','update']);

    Flight::start();
?>