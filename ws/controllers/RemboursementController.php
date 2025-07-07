<?php
    class RemboursementController {
        public static function create() {
            try {
                $data = Flight::request()->data;
                if (empty($data['pret_id']) || empty($data['montant'])) {
                    Flight::halt(400, json_encode(['error' => 'Données manquantes']));
                }
        
                $db = getDB();

                // Vérifier que prêt existe et est actif
                $stmtPret = $db->prepare("SELECT est_actif FROM pret WHERE id = ?");
                $stmtPret->execute([$data['pret_id']]);
                $pret = $stmtPret->fetch(PDO::FETCH_ASSOC);

                if (!$pret) {
                    Flight::halt(404, json_encode(['error' => 'Prêt non trouvé']));
                }
                if (!$pret['est_actif']) {
                    Flight::halt(400, json_encode(['error' => 'Prêt déjà clôturé']));
                }

                // Ajouter remboursement
                $stmtInsert = $db->prepare("INSERT INTO remboursement (pret_id, montant) VALUES (?, ?)");
                $stmtInsert->execute([$data['pret_id'], $data['montant']]);

                // Optionnel: vérifier si le prêt est totalement remboursé et le clôturer
                $stmtSomme = $db->prepare("SELECT montant FROM pret WHERE id = ?");
                $stmtSomme->execute([$data['pret_id']]);
                $totalPret = $stmtSomme->fetchColumn();

                $stmtRemb = $db->prepare("SELECT SUM(montant) FROM remboursement WHERE pret_id = ?");
                $stmtRemb->execute([$data['pret_id']]);
                $totalRemb = $stmtRemb->fetchColumn();

                if ($totalRemb >= $totalPret) {
                    $stmtClose = $db->prepare("UPDATE pret SET est_actif = 0 WHERE id = ?");
                    $stmtClose->execute([$data['pret_id']]);
                }

                Flight::json(['success' => true]);

            } catch (Exception $e) {
                Flight::halt(500, json_encode(['error' => 'Erreur serveur']));
            }
        }
    }
?>