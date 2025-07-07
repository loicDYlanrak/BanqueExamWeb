<?php
class RapportController
{
    public static function getBeneficesTotaux()
    {
        $db = getDB();

        // Calcul des intérêts totaux perçus
        $stmt = $db->prepare("
            SELECT SUM(r.montant - (p.montant / p.duree_mois)) as benefices
            FROM remboursement r
            JOIN pret p ON r.pret_id = p.id
            WHERE p.est_actif = TRUE
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        Flight::json([
            'benefices_totaux' => $result['benefices'] ?? 0
        ]);
    }

    public static function getInteretsMois()
    {
        $db = getDB();
        $debut = Flight::request()->query['debut'] ?? null;
        $fin = Flight::request()->query['fin'] ?? null;

        $where = "";
        if ($debut && $fin) {
            $where = "WHERE DATE_FORMAT(r.date_remboursement, '%Y-%m') BETWEEN :debut AND :fin";
        }

        $sql = "
        SELECT 
            YEAR(r.date_remboursement) as annee,
            MONTH(r.date_remboursement) as mois,
            SUM(r.montant - (p.montant / p.duree_mois)) as interets,
            COUNT(DISTINCT p.id) as nombre_prets
        FROM remboursement r
        JOIN pret p ON r.pret_id = p.id
        $where
        GROUP BY YEAR(r.date_remboursement), MONTH(r.date_remboursement)
        ORDER BY annee, mois
    ";

        $stmt = $db->prepare($sql);

        if ($debut && $fin) {
            $stmt->execute([':debut' => $debut, ':fin' => $fin]);
        } else {
            $stmt->execute();
        }

        Flight::json($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function getPretsEnRetard()
    {
        $db = getDB();
        $simulationDate = Flight::request()->query['simulation'] ?? null;

        $dateCondition = $simulationDate ?
            "AND p.date_debut <= LAST_DAY(:simulationDate)" :
            "AND p.date_debut <= CURDATE()";

        $sql = "
        SELECT 
            p.id,
            c.nom,
            c.prenom,
            p.montant,
            p.date_debut,
            p.duree_mois,
            tp.nom as type_pret,
            COUNT(r.id) as remboursements_effectues,
            TIMESTAMPDIFF(MONTH, p.date_debut, :currentDate) as mois_ecoules,
            (p.montant / p.duree_mois) as mensualite,
            (TIMESTAMPDIFF(MONTH, p.date_debut, :currentDate) - COUNT(r.id)) as retards
        FROM pret p
        JOIN client c ON p.client_id = c.id
        JOIN type_pret tp ON p.type_pret_id = tp.id
        LEFT JOIN remboursement r ON r.pret_id = p.id
        WHERE p.est_actif = TRUE
        $dateCondition
        GROUP BY p.id
        HAVING retards > 0
    ";

    echo $sql;

        $params = [
            ':currentDate' => $simulationDate ? $simulationDate . '-01' : date('Y-m-d')
        ];

        if ($simulationDate) {
            $params[':simulationDate'] = $simulationDate . '-01';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pretsEnRetard = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalRetards = array_reduce($pretsEnRetard, function ($carry, $pret) {
            return $carry + ($pret['mensualite'] * $pret['retards']);
        }, 0);

        Flight::json([
            'prets_en_retard' => $pretsEnRetard,
            'total_retards' => $totalRetards,
            'nombre_prets_en_retard' => count($pretsEnRetard)
        ]);
    }

    public static function calculMensualite()
    {
        $request = Flight::request();
        $data = $request->data;

        $montant = $data['montant'];
        $tauxAnnuel = $data['taux'];
        $dureeMois = $data['duree_mois'];

        if (!$montant || !$tauxAnnuel || !$dureeMois) {
            Flight::halt(400, json_encode(['error' => 'Paramètres manquants']));
        }

        $tauxMensuel = ($tauxAnnuel / 12) / 100;
        $mensualite = ($montant * $tauxMensuel) / (1 - pow(1 + $tauxMensuel, -$dureeMois));

        Flight::json([
            'montant' => $montant,
            'taux_annuel' => $tauxAnnuel,
            'duree_mois' => $dureeMois,
            'mensualite' => round($mensualite, 2)
        ]);
    }
}
