-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_petitions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_petitions;

-- Table Petition
CREATE TABLE IF NOT EXISTS Petition (
    IDP INT PRIMARY KEY AUTO_INCREMENT,
    TitreP VARCHAR(255) NOT NULL,
    DescriptionP TEXT NOT NULL,
    DateAjoutP DATETIME DEFAULT CURRENT_TIMESTAMP,
    DateFinP DATE NULL,
    NomPorteurP VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Signature
CREATE TABLE IF NOT EXISTS Signature (
    IDS INT PRIMARY KEY AUTO_INCREMENT,
    IDP INT NOT NULL,
    NomS VARCHAR(100) NOT NULL,
    PrenomS VARCHAR(100) NOT NULL,
    PaysS VARCHAR(100) NOT NULL,
    DateS DATETIME DEFAULT CURRENT_TIMESTAMP,
    HeureS TIME DEFAULT CURRENT_TIME,
    EmailS VARCHAR(100) NOT NULL,
    FOREIGN KEY (IDP) REFERENCES Petition(IDP) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test
INSERT INTO Petition (TitreP, DescriptionP, NomPorteurP, Email, DateFinP) VALUES
('Sauvons les océans', 'Pétition pour la protection des océans et la lutte contre la pollution plastique. Ensemble, agissons pour préserver notre planète bleue.', 'Jean Dupont', 'jean.dupont@email.com', '2025-12-31'),
('Éducation gratuite pour tous', 'Accès à l\'éducation gratuite et de qualité pour tous les enfants du monde. L\'éducation est un droit fondamental.', 'Marie Martin', 'marie.martin@email.com', '2025-11-30'),
('Énergies renouvelables maintenant', 'Transition vers les énergies renouvelables pour lutter contre le changement climatique. Agissons avant qu\'il ne soit trop tard.', 'Ahmed Benali', 'ahmed.benali@email.com', '2026-01-15');

-- Signatures de test
INSERT INTO Signature (IDP, NomS, PrenomS, PaysS, EmailS) VALUES
(1, 'Alami', 'Fatima', 'Maroc', 'fatima.alami@email.com'),
(1, 'Garcia', 'Carlos', 'Espagne', 'carlos.garcia@email.com'),
(1, 'Dubois', 'Sophie', 'France', 'sophie.dubois@email.com'),
(2, 'Johnson', 'Michael', 'USA', 'michael.j@email.com'),
(2, 'Smith', 'Emma', 'UK', 'emma.smith@email.com'),
(3, 'Hassan', 'Omar', 'Maroc', 'omar.hassan@email.com');