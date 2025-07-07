    <?php
    require 'vendor/autoload.php';
    require 'controllers/TypePretController.php';
    require 'db.php';

    // === CONFIG CORS ===
    header("Access-Control-Allow-Origin: *"); 
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // Gestion requêtes OPTIONS (préflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Lister tous les types
    Flight::route('GET /type_pret', ['TypePretController', 'getAll']);
    Flight::route('GET /type_pret/@id', ['TypePretController', 'getById']);
    Flight::route('POST /type_pret', ['TypePretController', 'create']);
    Flight::route('PUT /type_pret/@id', ['TypePretController', 'update']);
    Flight::route('DELETE /type_pret/@id', ['TypePretController', 'delete']);

    Flight::start();