<?php
require 'vendor/autoload.php';
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

Flight::route('OPTIONS /*', function() {
    Flight::json([], 200);
});
Flight::route('POST /fonds', function() {
    $request = Flight::request();
    $data = json_decode($request->getBody(), true);
    
    if(!isset($data['montant']) || !isset($data['description'])) {
        Flight::halt(400, json_encode(['error' => 'Données manquantes']));
    }
    
    try {
        $db = getDB();
        
        // Vérification du solde pour les sorties
        if ($data['montant'] < 0) {
            $stmt = $db->query("SELECT SUM(montant) as solde FROM fonds");
            $solde = $stmt->fetch(PDO::FETCH_ASSOC)['solde'];
            
            if ($solde < abs($data['montant'])) {
                Flight::halt(400, json_encode([
                    'error' => 'Solde insuffisant',
                    'solde_disponible' => $solde,
                    'montant_demande' => abs($data['montant'])
                ]));
            }
        }
        
        $stmt = $db->prepare("INSERT INTO fonds (montant, description) VALUES (?, ?)");
        $stmt->execute([$data['montant'], $data['description']]);
        
        Flight::json([
            'id' => $db->lastInsertId(),
            'message' => 'Fonds ajoutés avec succès'
        ]);
    } catch (PDOException $e) {
        Flight::halt(500, json_encode(['error' => 'Erreur de base de données']));
    }
});

// Route pour obtenir l'historique des fonds (GET)
Flight::route('GET /fonds', function(){
    $db = getDB();
    $stmt = $db->query("SELECT * FROM fonds ORDER BY date_ajout DESC");
    Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
});

// Route pour obtenir le solde total des fonds (GET)
Flight::route('GET /fonds/solde', function(){
    $db = getDB();
    $stmt = $db->query("SELECT SUM(montant) as solde_total FROM fonds");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    Flight::json($result);
});

Flight::start();