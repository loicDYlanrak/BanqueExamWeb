<?php
require_once 'models/CalculFinance.php';

class PretController {
    public static function create() {
        try {
            $data = Flight::request()->data;

            // Validation des données requises
            if (empty($data['client_id']) || empty($data['type_pret_id']) || empty($data['montant'])) {
                Flight::halt(400, json_encode(['error' => 'Données manquantes']));
            }

            $db = getDB();

            // Vérifier si le client existe
            $clientStmt = $db->prepare("SELECT id FROM client WHERE id = ?");
            $clientStmt->execute([$data['client_id']]);
            if (!$clientStmt->fetch()) {
                Flight::halt(404, json_encode(['error' => 'Client non trouvé']));
            }

            // Vérifier si le type de prêt existe et récupérer taux + durée
            $typeStmt = $db->prepare("SELECT taux, duree_mois FROM type_pret WHERE id = ?");
            $typeStmt->execute([$data['type_pret_id']]);
            $type = $typeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$type) {
                Flight::halt(404, json_encode(['error' => 'Type de prêt non trouvé']));
            }

            // Calcul des mensualités
            $mensualite = CalculFinance::calculerMensualite(
                $data['montant'],
                $type['taux'],
                $type['duree_mois']
            );

            // Insertion du prêt (la table pret n’a pas duree_mois)
            $insertStmt = $db->prepare("
                INSERT INTO pret (client_id, type_pret_id, montant, date_debut, est_actif)
                VALUES (?, ?, ?, NOW(), TRUE)
            ");
            $insertStmt->execute([
                $data['client_id'],
                $data['type_pret_id'],
                $data['montant']
            ]);

            // Réponse JSON
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
