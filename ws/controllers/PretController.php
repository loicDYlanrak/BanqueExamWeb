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
                VALUES (?, ?, ?, ?, TRUE)
            ");
            $insertStmt->execute([
                $data['client_id'],
                $data['type_pret_id'],
                $data['montant'],
                $data['date_debut']
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

    public static function prevoirMensualite() {
        try {
            $data = Flight::request()->data;

            if (empty($data['type_pret_id']) || empty($data['montant'])) {
                Flight::halt(400, json_encode(['error' => 'Paramètres manquants']));
            }

            $db = getDB();
            $stmt = $db->prepare("SELECT taux, duree_mois FROM type_pret WHERE id = ?");
            $stmt->execute([$data['type_pret_id']]);
            $type = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$type) {
                Flight::halt(404, json_encode(['error' => 'Type de prêt introuvable']));
            }

            // ✅ Appel direct de la méthode du modèle
            $mensualite = CalculFinance::calculerMensualite(
                $data['montant'],
                $type['taux'],
                $type['duree_mois']
            );

            Flight::json([
                'mensualite' => $mensualite,
                'duree_mois' => $type['duree_mois']
            ]);

        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => 'Erreur serveur']));
        }
    }

    public static function getAllWithFilters() {
        $db = getDB();

        $params = Flight::request()->query->getData();

        $sql = "SELECT p.id, p.client_id, c.nom, c.prenom, p.type_pret_id, t.nom as type_nom, t.taux, t.duree_mois, 
                    p.montant, p.date_debut, p.est_actif,
                    IFNULL(SUM(r.montant), 0) AS total_rembourse
                FROM pret p
                JOIN client c ON c.id = p.client_id
                JOIN type_pret t ON t.id = p.type_pret_id
                LEFT JOIN remboursement r ON r.pret_id = p.id
                GROUP BY p.id";

        // Gestion filtres
        $conditions = [];
        $paramsSQL = [];

        if (isset($params['est_actif'])) {
            $conditions[] = "p.est_actif = ?";
            $paramsSQL[] = $params['est_actif'] == '1' ? 1 : 0;
        }

        if (isset($params['en_retard']) && $params['en_retard'] == '1') {
            // Par exemple, on considère en retard si date_debut + duree_mois < aujourd'hui et prêt actif
            $conditions[] = "p.est_actif = 1 AND DATE_ADD(p.date_debut, INTERVAL t.duree_mois MONTH) < CURDATE()";
        }

        if ($conditions) {
            $sql .= " HAVING " . implode(" AND ", $conditions);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($paramsSQL);
        $prets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Flight::json($prets);
    }


}
?>
