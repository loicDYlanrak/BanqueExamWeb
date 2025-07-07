<?php
class CalculFinance {
    public static function calculerMensualite($capital, $tauxAnnuel, $dureeMois) {
        $tauxMensuel = ($tauxAnnuel / 12) / 100;

        if ($tauxMensuel == 0) {
            return $capital / $dureeMois; // Cas sans intérêt
        }

        return $capital * $tauxMensuel / 
            (1 - pow(1 + $tauxMensuel, -$dureeMois));
    }
    
    public static function calculerResteDu($pretId) {
        $db = getDB();

        // Total remboursé
        $stmtRemb = $db->prepare("
            SELECT SUM(montant) as total 
            FROM remboursement 
            WHERE pret_id = ?
        ");
        $stmtRemb->execute([$pretId]);
        $totalRemb = $stmtRemb->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Montant initial du prêt
        $stmtPret = $db->prepare("SELECT montant FROM pret WHERE id = ?");
        $stmtPret->execute([$pretId]);
        $pret = $stmtPret->fetch(PDO::FETCH_ASSOC);

        if (!$pret) {
            throw new Exception("Prêt introuvable");
        }

        return $pret['montant'] - $totalRemb;
    }

}