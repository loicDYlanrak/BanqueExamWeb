DROP DATABASE IF EXISTS banque;

CREATE DATABASE banque CHARACTER SET utf8mb4;

USE banque;
-- 1. Table des fonds de l'EF (pas besoin de table EF car unique)
CREATE TABLE fonds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    montant DECIMAL(12, 2) NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    description TEXT
);

-- 2. Table des types de prêts
CREATE TABLE type_pret (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL ,
    taux DECIMAL(5, 2) NOT NULL ,
    duree_mois INT NULL
);

-- 3. Table des clients
CREATE TABLE client (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(100)
);

-- 4. Table des prêts (liés à un client et un type)
CREATE TABLE pret (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    type_pret_id INT NOT NULL,
    montant DECIMAL(12, 2) NOT NULL,
    date_debut DATE NOT NULL,
    est_actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (client_id) REFERENCES client(id),
    FOREIGN KEY (type_pret_id) REFERENCES type_pret(id)
);

-- 5. Table des remboursements
CREATE TABLE remboursement (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pret_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_remboursement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pret_id) REFERENCES pret(id)
);

-- Assurez-vous d'abord que les clients existent
INSERT INTO client (nom, prenom, telephone, email) VALUES
('Dupont', 'Jean', '0612345678', 'jean.dupont@email.com'),
('Martin', 'Sophie', '0698765432', 'sophie.martin@email.com'),
('Durand', 'Pierre', '0623456789', 'pierre.durand@email.com'),
('Leroy', 'Marie', '0678912345', 'marie.leroy@email.com'),
('Moreau', 'Thomas', '0634567891', 'thomas.moreau@email.com');

-- Insérez les types de prêt s'ils n'existent pas
INSERT INTO type_pret (nom, taux, duree_mois) VALUES 
('Prêt personnel', 5.00, 24),
('Prêt immobilier', 3.50, 240),
('Prêt automobile', 4.25, 60),
('Crédit renouvelable', 7.50, 12);

INSERT INTO pret (client_id, type_pret_id, montant, date_debut, duree_mois, est_actif) VALUES
-- Prêt 1: En cours, sans retard
(1, 1, 10000.00, DATE_SUB(CURDATE(), INTERVAL 6 MONTH), 24, TRUE),

-- Prêt 2: En cours, avec 2 mois de retard
(2, 2, 150000.00, DATE_SUB(CURDATE(), INTERVAL 12 MONTH), 240, TRUE),

-- Prêt 3: Terminé (non actif)
(3, 3, 20000.00, DATE_SUB(CURDATE(), INTERVAL 60 MONTH), 60, FALSE),

-- Prêt 4: En cours, avec 1 mois de retard
(4, 1, 5000.00, DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 12, TRUE),

-- Prêt 5: Nouveau prêt, pas encore de retard
(5, 4, 3000.00, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 12, TRUE),

-- Prêt 6: En cours, sans retard (mais presque)
(1, 2, 80000.00, DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 120, TRUE);

-- Remboursements pour le prêt 1 (6 mois, pas de retard)
INSERT INTO remboursement (pret_id, montant, date_remboursement) VALUES
(1, 438.71, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(1, 438.71, DATE_SUB(CURDATE(), INTERVAL 4 MONTH)),
(1, 438.71, DATE_SUB(CURDATE(), INTERVAL 3 MONTH)),
(1, 438.71, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(1, 438.71, DATE_SUB(CURDATE(), INTERVAL 1 MONTH)),
(1, 438.71, CURDATE());

-- Remboursements pour le prêt 2 (12 mois écoulés, mais seulement 10 paiements)
INSERT INTO remboursement (pret_id, montant, date_remboursement) VALUES
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 10 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 9 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 7 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 6 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 4 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 3 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(2, 855.23, DATE_SUB(CURDATE(), INTERVAL 1 MONTH));

-- Remboursements pour le prêt 4 (3 mois écoulés, 2 paiements)
INSERT INTO remboursement (pret_id, montant, date_remboursement) VALUES
(4, 425.76, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
(4, 425.76, DATE_SUB(CURDATE(), INTERVAL 1 MONTH));