<?php
class RemboursementController {
    public static function create() {
        $request = Flight::request();
        
        // Récupérer les données en format application/x-www-form-urlencoded
        $pret_id = $request->data->pret_id;
        $montant = $request->data->montant;
        $date_paiement = $request->data->date_paiement ?? null;
        
        // Validation des données
        if(!isset($pret_id) || !isset($montant)) {
            Flight::halt(400, json_encode(['error' => 'Données manquantes: pret_id et montant sont requis']));
        }
        
        try {
            $db = getDB();
            
            // Vérifier si c'est le premier remboursement pour ce prêt
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM remboursement WHERE pret_id = ?");
            $stmt->execute([$pret_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $isFirstPayment = ($result['count'] == 0);
            
            // Récupérer les infos du prêt
            $stmt = $db->prepare("
                SELECT p.*, tp.taux, tp.duree_annee 
                FROM pret p
                JOIN type_pret tp ON p.type_pret_id = tp.id
                WHERE p.id = ? AND p.est_actif = TRUE
            ");
            $stmt->execute([$pret_id]);
            $pret = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$pret) {
                Flight::halt(404, json_encode(['error' => 'Prêt non trouvé ou inactif']));
            }
            
            // Vérifier si le montant est valide
            $montant = floatval($montant);
            if($montant <= 0) {
                Flight::halt(400, json_encode(['error' => 'Le montant doit être positif']));
            }
            
            // Date du remboursement
            $dateRemboursement = $date_paiement ?: date('Y-m-d H:i:s');
            
            // Insérer le remboursement
            $stmt = $db->prepare("
                INSERT INTO remboursement (pret_id, montant, date_remboursement)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pret_id, $montant, $dateRemboursement]);
            
            // Mettre à jour les fonds
            $stmt = $db->prepare("
                INSERT INTO fonds (montant, description)
                VALUES (?, ?)
            ");
            $description = "Remboursement prêt #" . $pret_id;
            $stmt->execute([$montant, $description]);
            
            $stmt = $db->prepare("INSERT INTO fonds (montant, description) VALUES (?, ?)");
            $stmt->execute([$montant, $description]);
            // Préparer la réponse
            $response = [
                'id' => $db->lastInsertId(),
                'message' => 'Remboursement enregistré avec succès',
                'montant' => $montant,
                'date' => $dateRemboursement
            ];
            
            // Ajouter un message si c'est le premier remboursement
            if($isFirstPayment) {
                $delaiPremierRemboursement = 1; // Délai en mois
                $response['notification'] = [
                    'message' => "C'est votre premier remboursement. Le prochain est attendu dans $delaiPremierRemboursement mois.",
                    'delai_mois' => $delaiPremierRemboursement
                ];
            }
            
            Flight::json($response);
            
        } catch (PDOException $e) {
            Flight::halt(500, json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]));
        }
    }

    public static function getByPretId($pretId) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT r.*, m.valeur_net 
                FROM remboursement r
                LEFT JOIN mensualite m ON r.pret_id = m.pret_id 
                    AND MONTH(r.date_remboursement) = m.mois 
                    AND YEAR(r.date_remboursement) = m.annee
                WHERE r.pret_id = ?
                ORDER BY r.date_remboursement
            ");

            $stmt->execute([$pretId]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Retourner les résultats au format JSON
            Flight::json($resultats);
            
        } catch (PDOException $e) {
            Flight::halt(500, json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]));
        }
    }
}
?>  