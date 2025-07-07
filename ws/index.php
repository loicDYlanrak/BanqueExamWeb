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

// Routes pour la gestion des clients
Flight::route('GET /clients', function() {
    $db = getDB();
    $search = Flight::request()->query['search'] ?? '';
    
    $sql = "SELECT * FROM client";
    if (!empty($search)) {
        $sql .= " WHERE nom LIKE :search OR prenom LIKE :search OR telephone LIKE :search";
        $stmt = $db->prepare($sql);
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $db->query($sql);
    }
    
    Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
});

Flight::route('GET /clients/@id', function($id) {
    $db = getDB();
    
    // Récupérer les infos du client
    $stmt = $db->prepare("SELECT * FROM client WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        Flight::halt(404, json_encode(['error' => 'Client non trouvé']));
    }
    
    // Récupérer ses prêts
    $stmt = $db->prepare("
        SELECT p.*, tp.nom as type_pret 
        FROM pret p
        JOIN type_pret tp ON p.type_pret_id = tp.id
        WHERE p.client_id = ?
        ORDER BY p.date_debut DESC
    ");
    $stmt->execute([$id]);
    $prets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $client['prets'] = $prets;
    Flight::json($client);
});

Flight::route('POST /clients', function() {
    $request = Flight::request();
    $data = json_decode($request->getBody(), true);
    
    // Validation minimale
    if (empty($data['nom'])) {
        Flight::halt(400, json_encode(['error' => 'Le nom est obligatoire']));
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO client (nom, prenom, telephone, email) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['nom'],
            $data['prenom'] ?? '',
            $data['telephone'] ?? '',
            $data['email'] ?? ''
        ]);
        
        Flight::json([
            'id' => $db->lastInsertId(),
            'message' => 'Client ajouté avec succès'
        ]);
    } catch (PDOException $e) {
        Flight::halt(500, json_encode(['error' => 'Erreur de base de données']));
    }
});

Flight::start();