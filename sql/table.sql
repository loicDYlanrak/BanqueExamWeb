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
    duree_mois INT NOT NULL,
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