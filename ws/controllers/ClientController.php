<?php
    class ClientController {
        public static function getAll() {
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
        }

        public static function getById($id) {
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
            $client['prets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Flight::json($client);
        }

        public static function create() {
            $request = Flight::request();
            $data = json_decode($request->getBody(), true);
            
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
        }
    }
?>