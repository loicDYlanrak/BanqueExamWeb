<?php
class CalculFinance {
    public static function calculerMensualite($capital, $tauxAnnuel, $dureeMois) {
        $tauxMensuel = ($tauxAnnuel / 12) / 100;
        return $capital * $tauxMensuel / 
               (1 - pow(1 + $tauxMensuel, $dureeMois));
    }
    
    public static function calculerResteDu($pretId) {
        $db = getDB();
        
        $totalRembourse = $db->prepare("
            SELECT SUM(montant) as total 
            FROM remboursement 
            WHERE pret_id = ?
        ");
        $totalRembourse->execute([$pretId]);
        
        $pret = $db->prepare("SELECT montant FROM pret WHERE id = ?");
        $pret->execute([$pretId]);
        
        return $pret->fetch(PDO::FETCH_ASSOC)['montant'] - 
               $totalRembourse->fetch(PDO::FETCH_ASSOC)['total'];
    }
}