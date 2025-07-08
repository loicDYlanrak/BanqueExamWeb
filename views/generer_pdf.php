<?php
require('fpdf.php');

class PDF extends FPDF
{
    function Header()
    {
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 15);
        // Décalage à droite
        $this->Cell(80);
        // Titre
        $this->Cell(30, 10, iconv('UTF-8', 'ISO-8859-1', 'DÉTAILS DU PRÊT'), 0, 0, 'C');
        // Saut de ligne
        $this->Ln(20);
    }

    function Footer()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Page ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function CreateTable($header, $data)
    {
        // Largeurs des colonnes
        $w = array(20, 25, 30, 30, 30, 30, 30);
        // En-tête
        foreach($header as $key => $col) {
            $header[$key] = iconv('UTF-8', 'ISO-8859-1', $col);
        }
        
        for($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
        $this->Ln();
        
        // Données
        foreach($data as $row)
        {
            $this->Cell($w[0], 6, $row['periode'], 'LR');
            $this->Cell($w[1], 6, $row['annee'], 'LR');
            $this->Cell($w[2], 6, $this->formatAriary($row['pret']), 'LR', 0, 'R');
            $this->Cell($w[3], 6, $this->formatAriary($row['interet']), 'LR', 0, 'R');
            $this->Cell($w[4], 6, $this->formatAriary($row['assurance']), 'LR', 0, 'R');
            $this->Cell($w[5], 6, $this->formatAriary($row['mensualite']), 'LR', 0, 'R');
            $this->Cell($w[6], 6, $this->formatAriary($row['valeur_net']), 'LR', 0, 'R');
            $this->Ln();
        }
        // Trait de terminaison
        $this->Cell(array_sum($w), 0, '', 'T');
    }
    
    // Fonction pour formater les nombres en Ariary
    function formatAriary($amount)
    {
        return number_format($amount, 0, ',', ' ') . ' Ar';
    }
}

// Récupérer les données POST
$pretData = json_decode($_POST['pret_data'], true);
$echeancierData = json_decode($_POST['echeancier_data'], true);

// Créer le PDF
$pdf = new PDF();
$pdf->AliasNbPages();

// Ajouter une page avec les détails du prêt
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Fiche du Prêt #') . $pretData['id'], 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Client:'));
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', $pretData['nom'] . ' ' . $pretData['prenom']), 0, 1);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Type de prêt:'));
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', $pretData['type_nom']) . ' (' . $pretData['taux'] . '%)', 0, 1);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Montant:'));
$pdf->Cell(0, 10, $pdf->formatAriary($pretData['montant']), 0, 1);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Date de début:'));
$pdf->Cell(0, 10, date('d/m/Y', strtotime($pretData['date_debut'])), 0, 1);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Durée:'));
$pdf->Cell(0, 10, $pretData['duree_mois'] . ' ' . iconv('UTF-8', 'ISO-8859-1', 'mois'), 0, 1);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-1', 'Statut:'));
$pdf->Cell(0, 10, $pretData['est_actif'] ? iconv('UTF-8', 'ISO-8859-1', 'Actif') : iconv('UTF-8', 'ISO-8859-1', 'Clôturé'), 0, 1);
$pdf->Ln(15);

// Ajouter l'échéancier
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1', 'Échéancier du prêt'), 0, 1);
$pdf->Ln(5);

$header = array('Période', 'Année', 'Capital', 'Intérêt', 'Assurance', 'Mensualité', 'Reste');
$pdf->SetFont('Arial', '', 10);
$pdf->CreateTable($header, $echeancierData);

// Générer le PDF et le forcer à télécharger
$pdf->Output('D', 'pret_' . $pretData['id'] . '.pdf');
?>