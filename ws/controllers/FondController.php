<?php
    class FondController {
        public static function create() {
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
        }

        public static function getAll() {
            $db = getDB();
            $stmt = $db->query("SELECT * FROM fonds ORDER BY date_ajout DESC");
            Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        public static function getSolde() {
            $db = getDB();
            $stmt = $db->query("SELECT SUM(montant) as solde_total FROM fonds");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            Flight::json($result);
        }
    }
?>