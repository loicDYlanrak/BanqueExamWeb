<?php
class RapportController
{

    public static function getFondsDisponiblesParMois()
    {
        $db = getDB();
        $debut = Flight::request()->query['debut'] ?? null;
        $fin = Flight::request()->query['fin'] ?? null;

        $where = "WHERE 1=1";
        $params = [];

        if ($debut && $fin) {
            $where .= " AND CONCAT(annee, '-', LPAD(mois, 2, '0')) BETWEEN :debut AND :fin";
            $params[':debut'] = $debut;
            $params[':fin'] = $fin;
        }

        $sql = "
    SELECT 
        mois,
        annee,
        SUM(fonds_disponibles) as fonds_disponibles,
        SUM(montant_non_emprunte) as montant_non_emprunte,
        SUM(remboursements) as remboursements
    FROM (
        -- Fonds initiaux non empruntés
        SELECT 
            MONTH(:current_date) as mois, 
            YEAR(:current_date) as annee,
            (SELECT montant FROM fonds ORDER BY id DESC LIMIT 1) - 
            COALESCE(SUM(p.montant), 0) as montant_non_emprunte,
            0 as remboursements,
            (SELECT montant FROM fonds ORDER BY id DESC LIMIT 1) - 
            COALESCE(SUM(p.montant), 0) as fonds_disponibles
        FROM pret p
        WHERE p.est_actif = TRUE
        
        UNION ALL
        
        -- Remboursements par mois
        SELECT 
            MONTH(r.date_remboursement) as mois,
            YEAR(r.date_remboursement) as annee,
            0 as montant_non_emprunte,
            SUM(r.montant) as remboursements,
            SUM(r.montant) as fonds_disponibles
        FROM remboursement r
        JOIN pret p ON r.pret_id = p.id
        WHERE p.est_actif = TRUE
        GROUP BY YEAR(r.date_remboursement), MONTH(r.date_remboursement)
    ) as combined_data
    $where
    GROUP BY annee, mois
    ORDER BY annee, mois
    ";

        $params[':current_date'] = date('Y-m-d');

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($item) {
            return [
                'mois' => $item['mois'],
                'annee' => $item['annee'],
                'fonds_disponibles' => (float)$item['fonds_disponibles'],
                'montant_non_emprunte' => (float)$item['montant_non_emprunte'],
                'remboursements' => (float)$item['remboursements'],
                'mois_annee' => sprintf("%02d/%04d", $item['mois'], $item['annee'])
            ];
        }, $results);

        Flight::json($formatted);
    }
    public static function getBeneficesTotaux()
    {
        $db = getDB();

        // Calcul des intérêts totaux perçus (utilise maintenant duree_annee)
        $stmt = $db->prepare("
        SELECT SUM(r.montant - (p.montant / (tp.duree_annee * 12))) as benefices
        FROM remboursement r
        JOIN pret p ON r.pret_id = p.id
        JOIN type_pret tp ON p.type_pret_id = tp.id
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

        $where = "WHERE 1=1";
        $params = [];

        if ($debut && $fin) {
            $where .= " AND CONCAT(m.annee, '-', LPAD(m.mois, 2, '0')) BETWEEN :debut AND :fin";
            $params[':debut'] = $debut;
            $params[':fin'] = $fin;
        }

        // Requête corrigée avec LEFT JOIN au lieu de sous-requête dans SUM
        $sql = "
        SELECT 
            m.mois,
            m.annee,
            SUM(m.interet) as interets_theoriques,
            SUM(
                CASE WHEN r.id IS NOT NULL THEN 
                    GREATEST(r.montant - (m.amortissement + (m.valeur_net * tp.assurance/100/12)), 0)
                ELSE 
                    0 
                END
            ) as interets_reels,
            COUNT(DISTINCT m.pret_id) as nombre_prets
        FROM mensualite m
        JOIN pret p ON m.pret_id = p.id
        JOIN type_pret tp ON p.type_pret_id = tp.id
        LEFT JOIN remboursement r ON r.pret_id = m.pret_id 
            AND YEAR(r.date_remboursement) = m.annee 
            AND MONTH(r.date_remboursement) = m.mois
        $where
        GROUP BY m.annee, m.mois
        ORDER BY m.annee, m.mois
    ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function ($item) {
            return [
                'mois' => $item['mois'],
                'annee' => $item['annee'],
                'interets_theoriques' => (float)$item['interets_theoriques'],
                'interets_reels' => (float)$item['interets_reels'],
                'nombre_prets' => (int)$item['nombre_prets'],
                'mois_annee' => sprintf("%02d/%04d", $item['mois'], $item['annee'])
            ];
        }, $results);

        Flight::json($formatted);
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
            tp.duree_annee,
            tp.nom as type_pret,
            COUNT(r.id) as remboursements_effectues,
            TIMESTAMPDIFF(MONTH, p.date_debut, :currentDate) as mois_ecoules,
            (p.montant / (tp.duree_annee * 12)) as mensualite,
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
        $dureeAnnee = $data['duree_annee'];

        if (!$montant || !$tauxAnnuel || !$dureeAnnee) {
            Flight::halt(400, json_encode(['error' => 'Paramètres manquants']));
        }

        $dureeMois = $dureeAnnee * 12;
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
