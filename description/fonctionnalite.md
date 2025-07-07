Fonctionnalités par Page
1. Dashboard
Afficher le solde actuel des fonds de l'EF.

Nombre de prêts actifs/en retard.

Graphique des prêts par type.

2. Gestion des Fonds
Ajouter des fonds : Formulaire (montant, description).

Historique : Liste des entrées/sorties de fonds.

3. Types de Prêts
Liste : Afficher tous les types (nom, taux, durée).

Ajouter/Modifier : Formulaire pour créer/modifier un type.

4. Clients
Liste : Recherche par nom/téléphone.

Fiche client : Détails + liste de ses prêts.

Ajouter : Formulaire simple (nom, prénom, contact).

5. Gestion des Prêts
Nouveau prêt :

Sélection du client + type de prêt.

Calcul automatique des mensualités (montant * taux / durée).

Liste des prêts :

Filtres (actifs/clôturés, en retard).

Bouton "Rembourser" pour enregistrer un paiement.

Détail d'un prêt :

Historique des remboursements.

Calcul du reste dû (avec intérêts).

6. Rapports
Bénéfices totaux (intérêts perçus).

Prêts en retard.

Notes Supplémentaires
Pas de multi-EF : Les fonds sont globaux, pas besoin de lier à un EF.

Sécurité : Ajouter un système d'authentification pour les gestionnaires.

Calculs :

Exemple de mensualité : Pour un prêt de 10 000€ à 5% sur 2 ans :
M = [10 000 × (5/12)/100] / [1 − (1 + (5/12)/100)^−24] ≈ 438.71€/mois.

Cette structure couvre l'essentiel tout restant simple. Si besoin d'extensions (garanties, pénalités de retard), on peut ajouter des tables.