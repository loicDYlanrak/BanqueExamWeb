<?php
    require_once 'models/CalculFinance.php';

    class PretController {
        public static function create() {
            try {
                $data = Flight::request()->data;
                
                // Validation
                if (empty($data['client_id']) || empty($data['type_pret_id']) || empty($data['montant'])) {
                    Flight::halt(400, json_encode(['error' => 'Données manquantes']));
                }
                
                $db = getDB();
                
                // Vérification des clés étrangères
                $clientExists = $db->prepare("SELECT id FROM client WHERE id = ?");
                $clientExists->execute([$data['client_id']]);
                if (!$clientExists->fetch()) {
                    Flight::halt(404, json_encode(['error' => 'Client non trouvé']));
                }
                
                $typeExists = $db->prepare("SELECT taux, duree_mois FROM type_pret WHERE id = ?");
                $typeExists->execute([$data['type_pret_id']]);
                $type = $typeExists->fetch(PDO::FETCH_ASSOC);
                if (!$type) {
                    Flight::halt(404, json_encode(['error' => 'Type de prêt non trouvé']));
                }
                
                // Calcul des mensualités
                $mensualite = CalculFinance::calculerMensualite(
                    $data['montant'],
                    $type['taux'],
                    $type['duree_mois']
                );
                
                // Création du prêt (adapté à votre schéma avec est_actif)
                $stmt = $db->prepare("
                    INSERT INTO pret 
                    (client_id, type_pret_id, montant, date_debut, duree_mois, est_actif) 
                    VALUES (?, ?, ?, NOW(), ?, TRUE)
                ");
                $stmt->execute([
                    $data['client_id'],
                    $data['type_pret_id'],
                    $data['montant'],
                    $type['duree_mois']
                ]);
                
                // Retourne aussi l'ID du nouveau prêt et la durée
                Flight::json([
                    'success' => true,
                    'id' => $db->lastInsertId(),
                    'mensualite' => $mensualite,
                    'duree_mois' => $type['duree_mois']
                ]);
                
            } catch (PDOException $e) {
                error_log("Erreur base de données: " . $e->getMessage());
                Flight::halt(500, json_encode(['error' => 'Erreur serveur']));
            }
        }
    }
?>