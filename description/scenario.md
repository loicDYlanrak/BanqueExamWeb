### Scénario Illustratif avec la Base de Donnée

Prenons un exemple concret avec **l'EF "CreditPlus"** et un client **Jean Dupont**.  
Voici ce qui se passe dans la base de données à chaque étape :

---

#### **1. Ajout de Fonds dans l'EF**  
**Scénario** : CreditPlus ajoute 100 000Ar de capital initial.  

**Action en Base** :  
```sql
INSERT INTO fonds (montant, description) 
VALUES (100000.00, 'Capital initial');
```

**Résultat** :  
| id | montant  | date_ajout         | description     |
|----|----------|--------------------|-----------------|
| 1  | 100000.00| 2023-10-01 09:00:00| Capital initial |

→ Les fonds disponibles pour les prêts sont maintenant de **100 000Ar**.

---

#### **2. Création d'un Type de Prêt**  
**Scénario** : CreditPlus crée un type "Prêt voiture" à taux 7% sur 36 mois.  

**Action en Base** :  
```sql
INSERT INTO type_pret (nom, taux, duree_mois) 
VALUES ('Prêt voiture', 7.00, 36);
```

**Résultat** :  
| id | nom           | taux | duree_mois |
|----|---------------|------|------------|
| 1  | Prêt voiture  | 7.00 | 36         |

→ Maintenant, les clients peuvent demander ce type de prêt.

---

#### **3. Enregistrement d'un Client**  
**Scénario** : Jean Dupont (tel: 0612345678) s'inscrit.  

**Action en Base** :  
```sql
INSERT INTO client (nom, prenom, telephone) 
VALUES ('Dupont', 'Jean', '0612345678');
```

**Résultat** :  
| id | nom    | prenom | telephone  | email |
|----|--------|--------|------------|-------|
| 1  | Dupont | Jean   | 0612345678 | NULL  |

---

#### **4. Accord d'un Prêt à Jean**  
**Scénario** : Jean emprunte 15 000Ar pour une voiture (type ID 1).  

**Calcul** :  
- Taux mensuel = `7% / 12 ≈ 0.583%`  
- Mensualité = `[15 000 × 0.00583] / [1 − (1 + 0.00583)^−36] ≈ 463.12Ar/mois`.  

**Action en Base** :  
```sql
-- Étape 1 : Création du prêt
INSERT INTO pret (client_id, type_pret_id, montant, date_debut, duree_mois) 
VALUES (1, 1, 15000.00, '2023-10-05', 36);

-- Étape 2 : Mise à jour des fonds (débit)
INSERT INTO fonds (montant, description) 
VALUES (-15000.00, 'Débit pour prêt ID 1');
```

**Résultat** :  
**Table `pret`** :  
| id | client_id | type_pret_id | montant  | date_debut | duree_mois | est_actif |
|----|-----------|--------------|----------|------------|------------|-----------|
| 1  | 1         | 1            | 15000.00 | 2023-10-05 | 36         | TRUE      |

**Table `fonds`** :  
| id | montant   | date_ajout         | description           |
|----|-----------|--------------------|-----------------------|
| 1  | 100000.00 | 2023-10-01 09:00:00| Capital initial       |
| 2  | -15000.00 | 2023-10-05 10:30:00| Débit pour prêt ID 1  |

→ Fonds restants : **85 000Ar** (100k - 15k).

---

#### **5. Remboursement par Jean**  
**Scénario** : Jean paie sa 1ère mensualité (463.12Ar) le 05/11/2023.  

**Action en Base** :  
```sql
-- Étape 1 : Enregistrement du remboursement
INSERT INTO remboursement (pret_id, montant) 
VALUES (1, 463.12);

-- Étape 2 : Mise à jour des fonds (crédit partiel)
INSERT INTO fonds (montant, description) 
VALUES (463.12, 'Remboursement prêt ID 1 - mois 1');
```

**Résultat** :  
**Table `remboursement`** :  
| id | pret_id | montant | date_remboursement    |
|----|---------|---------|-----------------------|
| 1  | 1       | 463.12  | 2023-11-05 14:15:00   |

**Table `fonds`** :  
| id | montant   | date_ajout         | description                          |
|----|-----------|--------------------|--------------------------------------|
| ...| ...       | ...                | ...                                  |
| 3  | 463.12    | 2023-11-05 14:15:00| Remboursement prêt ID 1 - mois 1     |

→ Fonds mis à jour : **85 000Ar + 463.12Ar = 85 463.12Ar**.  

---

### Visualisation des Données après 1 an (Exemple)  
- **Prêt ID 1** : 12 remboursements (463.12 × 12 = 5 557.44Ar).  
- **Reste dû** :  
  - Intérêts totaux initiaux : ~1 800Ar (7% de 15k).  
  - Capital remboursé : ~3 757.44Ar (5 557.44 - intérêts).  
  - **Reste** : 15 000 - 3 757.44 ≈ **11 242.56Ar**.  

---

### Résumé des Effets dans la Base  
- **Fonds** : Diminué par les prêts, augmenté par les remboursements (+intérêts).  
- **Prêts** : `est_actif` passe à `FALSE` quand tout est remboursé.  
- **Remboursements** : Historique complet pour calculer le solde.  

Cette logique permet de suivre précisément l'argent et les engagements.


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
        
        GROUP BY p.id
        HAVING retards > 0