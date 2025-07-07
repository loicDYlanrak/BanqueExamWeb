<?php
require 'vendor/autoload.php';
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
Flight::route('GET /type_pret', function () {
    $stmt = getDB()->query("SELECT * FROM type_pret");
    Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
});

// Détail d'un type
Flight::route('GET /type_pret/@id', function ($id) {
    $stmt = getDB()->prepare("SELECT * FROM type_pret WHERE id = ?");
    $stmt->execute([$id]);
    Flight::json($stmt->fetch(PDO::FETCH_ASSOC));
});

// Ajouter un type
Flight::route('POST /type_pret', function () {
    $data = Flight::request()->data;
    $stmt = getDB()->prepare("INSERT INTO type_pret (nom, taux, duree_mois) VALUES (?, ?, ?)");
    $stmt->execute([$data['nom'], $data['taux'], $data['duree_mois']]);
    Flight::json(['success' => true]);
});

// Modifier un type
Flight::route('PUT /type_pret/@id', function ($id) {
    parse_str(file_get_contents("php://input"), $data);
    $stmt = getDB()->prepare("UPDATE type_pret SET nom = ?, taux = ?, duree_mois = ? WHERE id = ?");
    $stmt->execute([$data['nom'], $data['taux'], $data['duree_mois'], $id]);
    Flight::json(['success' => true]);
});

// Supprimer un type
Flight::route('DELETE /type_pret/@id', function ($id) {
    $stmt = getDB()->prepare("DELETE FROM type_pret WHERE id = ?");
    $stmt->execute([$id]);
    Flight::json(['success' => true]);
});

Flight::start();