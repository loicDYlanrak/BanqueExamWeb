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
            $typeStmt = $db->prepare("SELECT taux, duree_annee FROM type_pret WHERE id = ?");
            $typeStmt->execute([$data['type_pret_id']]);
            $type = $typeStmt->fetch(PDO::FETCH_ASSOC);
            if (!$type) {
                Flight::halt(404, json_encode(['error' => 'Type de prêt non trouvé']));
            }

            // Calcul des mensualités
            $mensualite = CalculFinance::calculerMensualite(
                $data['montant'],
                $type['taux'],
                $type['duree_annee']
            );

            // Insertion du prêt (la table pret n’a pas duree_annee)
            $insertStmt = $db->prepare("
                INSERT INTO pret (client_id, type_pret_id, montant, date_debut, est_actif)
                VALUES (?, ?, ?, ?, FALSE)
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
                'duree_annee' => $type['duree_annee']
            ]);

        } catch (PDOException $e) {
            error_log("Erreur base de données: " . $e->getMessage());
            Flight::halt(500, json_encode(['error' => 'Erreur serveur']));
        }
    }

    public static function genererEcheancierAnnuel($id) {
        $db = getDB();

        $stmt = $db->prepare("SELECT p.*, tp.taux, tp.assurance, tp.duree_annee
                                FROM pret p
                                JOIN type_pret tp ON p.type_pret_id = tp.id
                                WHERE p.id = ?");        $stmt->execute([$id]);
        $pret = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pret) {
            Flight::halt(404, json_encode(['error' => 'Prêt non trouvé']));
        }

        $montant = floatval($pret['montant']);
        $taux = floatval($pret['taux']);
        $dureeAnnees = intval($pret['duree_annee']); // ex: 5 ans
        $assurance = floatval($pret['assurance']);
        $dateDebut = $pret['date_debut'];
        $annuite = (new PretController())->genererAnnuitesPret(
                $montant, $taux, $dureeAnnees, 
                $assurance, $dateDebut
            );

        Flight::json($annuite);
    }

    public static function getEcheancierById($pretId) {
        try {
            $db = getDB();

            $stmt = $db->prepare("SELECT p.*, tp.taux, tp.assurance, tp.duree_annee
                                FROM pret p
                                JOIN type_pret tp ON p.type_pret_id = tp.id
                                WHERE p.id = ?");
            $stmt->execute([$pretId]);
            $pret = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pret) {
                Flight::halt(404, json_encode(['error' => 'Prêt introuvable']));
                return;
            }

            // Appel au générateur d’échéancier
            $echeancier = (new PretController())->genererEcheancierPret(
                floatval($pret['montant']),
                floatval($pret['taux']),
                intval($pret['duree_annee']),
                floatval($pret['assurance']),
                $pret['date_debut']
            );


            Flight::json($echeancier);

        } catch (Exception $e) {
            error_log("[ERREUR ECHEANCIER PAR ID] " . $e->getMessage());
            Flight::halt(500, json_encode(['error' => 'Erreur serveur']));
        }
    }


    function genererEcheancierPret($montant, $tauxAnnuel, $dureeAnnees, $assurance, $dateDebut) {
        try {
            $dureeMois = $dureeAnnees * 12;
            $tauxMensuel = $tauxAnnuel / 12 / 100;
            $assuranceMensuelle = $assurance / 12 / 100 * $montant;

            if ($tauxMensuel <= 0 || $dureeMois <= 0 || $montant <= 0) {
                throw new Exception("Paramètres invalides pour le calcul de l’échéancier.");
            }

            $mensualite = $montant * $tauxMensuel / (1 - pow(1 + $tauxMensuel, -$dureeMois));

            $reste = $montant;
            $resultats = [];

            $mois = (int)date('m', strtotime($dateDebut));
            $annee = (int)date('Y', strtotime($dateDebut));

            for ($i = 1; $i <= $dureeMois; $i++) {
                $pret = $reste;
                $interet = round($pret * $tauxMensuel, 2);
                $principal = round($mensualite - $interet, 2);
                $amortissement = $principal + $assuranceMensuelle;
                $valeurNet = $pret - $principal;

                $resultats[] = [
                    'periode' => "$mois/$annee",
                    'mois' => $mois,
                    'annee' => $annee,
                    'pret' => round($pret, 2),
                    'interet' => $interet,
                    'principal' => $principal,
                    'amortissement' => round($amortissement, 2),
                    'assurance' => round($assuranceMensuelle, 2),
                    'mensualite' => round($mensualite, 2),
                    'valeur_net' => round($valeurNet, 2)
                ];

                $reste = $valeurNet;

                // Avancer au mois suivant
                $mois++;
                if ($mois > 12) {
                    $mois = 1;
                    $annee++;
                }
            }

            return $resultats;

        } catch (Exception $e) {
            error_log("[ERREUR ECHEANCIER] " . $e->getMessage());
            return [];
        }
    }

    public static function genererAnnuitesPret($montant, $tauxAnnuel, $dureeAnnees, $assurance, $dateDebut) {
        $mensualites = (new PretController())->genererEcheancierPret($montant, $tauxAnnuel, $dureeAnnees, $assurance, $dateDebut);
        $annuites = [];
        $annee = 1;
        $mois = 1;

        if (empty($mensualites)) {
            return $annuites; // Rien à faire
        }

        foreach ($mensualites as $ligne) {

            if (!isset($annuites[$annee])) {
                // Init avec les valeurs du premier mois de l'année
                $annuites[$annee] = [
                    'mois' => $ligne['mois'],
                    'annee' => $ligne['annee'],
                    'periode' => (string)$annee,
                    'pret' => $ligne['pret'], // capital début année (1er mois)
                    'interet' => 0,
                    'principal' => 0,
                    'amortissement' => 0,
                    'assurance' => 0,
                    'mensualite' => 0,
                    'valeur_net' => 0, // sera mis à jour plus tard (dernier mois)
                ];
            }

            // Cumuler les valeurs
            $annuites[$annee]['interet'] += $ligne['interet'];
            $annuites[$annee]['principal'] += $ligne['principal'];
            $annuites[$annee]['amortissement'] += $ligne['amortissement'];
            $annuites[$annee]['assurance'] += $ligne['assurance'];
            $annuites[$annee]['mensualite'] += $ligne['mensualite'];

            // Mettre à jour la valeur_net (capital restant fin d'année) avec la dernière du mois
            $annuites[$annee]['valeur_net'] = $ligne['valeur_net'];
            if($mois >= 12) {
                $mois = 0;
                $annee++;
            }
            $mois++;
        }

        // Arrondir les valeurs des annuités
        foreach ($annuites as &$an) {
            $an['interet'] = round($an['interet'], 2);
            $an['principal'] = round($an['principal'], 2);
            $an['amortissement'] = round($an['amortissement'], 2);
            $an['assurance'] = round($an['assurance'], 2);
            $an['mensualite'] = round($an['mensualite'], 2);
            $an['pret'] = round($an['pret'], 2);
            $an['valeur_net'] = round($an['valeur_net'], 2);
        }
        unset($an);

        // Optionnel : réindexer en tableau simple (sans clé année)
        return array_values($annuites);
    }

    public static function getAllWithFilters()
    {
        $db = getDB();
        $sql = "SELECT p.*, 
                    c.nom, c.prenom, 
                    tp.nom AS type_nom, tp.taux 
                FROM pret p
                JOIN client c ON p.client_id = c.id
                JOIN type_pret tp ON p.type_pret_id = tp.id
                WHERE 1=1";

        $params = [];

        if (isset($_GET['est_actif'])) {
            $sql .= " AND p.est_actif = ?";
            $params[] = intval($_GET['est_actif']);
        }

        if (isset($_GET['en_retard']) && $_GET['en_retard'] == '1') {
            $sql .= " AND p.id IN (
                        SELECT pret_id 
                        FROM mensualite 
                        WHERE paye = false 
                        AND date_limite < NOW()
                    )";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $prets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajout du total remboursé
        foreach ($prets as &$pret) {
            $stmt2 = $db->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM remboursement WHERE pret_id = ?");
            $stmt2->execute([$pret['id']]);
            $pret['total_rembourse'] = $stmt2->fetchColumn();
        }

        Flight::json($prets);
    }


    public static function ValiPret($id)
    {
        try {
            $db = getDB();

            // 1. Récupérer le prêt
            $stmt = $db->prepare("SELECT p.*, tp.taux, tp.duree_annee, tp.assurance 
                                FROM pret p 
                                JOIN type_pret tp ON p.type_pret_id = tp.id 
                                WHERE p.id = ?");
            $stmt->execute([$id]);
            $pret = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pret) {
                Flight::halt(404, json_encode(['error' => 'Prêt introuvable']));
            }

            // 2. Générer l’échéancier
            $echeances = (new PretController())->genererEcheancierPret(
                $pret['montant'],
                $pret['taux'],
                $pret['duree_annee'],
                $pret['assurance'],
                $pret['date_debut']
            );

            // 3. Insertion dans mensualité
            $insert = $db->prepare("INSERT INTO mensualite 
                (pret_id, mois, annee, mensualite, interet, pret, amortissement, valeur_net) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($echeances as $echeance) {
                $insert->execute([
                    $id,
                    $echeance['mois'],
                    $echeance['annee'],
                    $echeance['mensualite'],
                    $echeance['interet'],
                    $echeance['pret'],
                    $echeance['amortissement'],
                    $echeance['valeur_net']
                ]);
            }

            // 4. Mise à jour du prêt : le rendre actif
            $update = $db->prepare("UPDATE pret SET est_actif = true WHERE id = ?");
            $update->execute([$id]);

            Flight::json(['status' => 'success']);
        } catch (Exception $e) {
            Flight::halt(500, json_encode(['error' => $e->getMessage()]));
        }
    }



}
?>
