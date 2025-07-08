<?php
    require 'db.php';
    require 'vendor/autoload.php';
    require 'controllers/TypePretController.php';
    require 'controllers/ClientController.php';
    require 'controllers/FondController.php';
    require 'controllers/PretController.php';
    require 'controllers/RapportController.php';
    require 'controllers/RemboursementController.php';

        // ===== CONFIGURATION CORS =====
    header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    // Répondre directement aux requêtes OPTIONS (préflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit();
    }

    // Routes pour les fonds
    Flight::route('POST /fonds', ['FondController', 'create']);
    Flight::route('GET /fonds', ['FondController', 'getAll']);
    Flight::route('GET /fonds/solde', ['FondController', 'getSolde']);

    // Routes pour les clients
    Flight::route('GET /clients', ['ClientController', 'getAll']);
    Flight::route('GET /clients/@id', ['ClientController', 'getById']);
    Flight::route('POST /clients', ['ClientController', 'create']);
    Flight::route('PUT /clients/@id', ['ClientController','update']);

    // Routes pour les rapports
    Flight::route('GET /rapports/totaux', ['RapportController','getBeneficesTotaux']);
    Flight::route('GET /rapports/retards', ['RapportController','getPretsEnRetard']);
    Flight::route('POST /rapports/calcul-mensualite', ['RapportController','calculMensualite']);
    Flight::route('GET /rapports/interets-mois', ['RapportController','getInteretsMois']);

    // Lister tous les types
    Flight::route('GET /type_pret', ['TypePretController', 'getAll']);
    Flight::route('GET /type_pret/@id', ['TypePretController', 'getById']);
    Flight::route('POST /type_pret', ['TypePretController', 'create']);
    Flight::route('PUT /type_pret/@id', ['TypePretController', 'update']);
    Flight::route('DELETE /type_pret/@id', ['TypePretController', 'delete']);
    // Clients
    Flight::route('GET /clients', ['ClientController', 'getAll']);
    
    // Prêts
    Flight::route('POST /prets', ['PretController', 'create']);

    // Récupérer les prêts avec filtres GET ?est_actif=1&en_retard=1
    Flight::route('GET /prets', ['PretController', 'getAllWithFilters']);
    Flight::route('GET /prets/@id/echeancier', ['PretController', 'getEcheancierById']);
    Flight::route('GET /prets/@id/annuite', ['PretController', 'genererEcheancierAnnuel']);
    Flight::route('POST /prets/@id/valider', ['PretController', 'ValiPret']);

    // Effectuer un remboursement POST /rembourser avec { pret_id, montant }
    Flight::route('POST /rembourser', ['RemboursementController', 'create']);


    Flight::start();
?>