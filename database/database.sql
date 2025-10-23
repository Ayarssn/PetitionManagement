-- Table Utilisateur (uniquement pour ceux qui veulent gérer des pétitions)
CREATE TABLE Utilisateur (
    IDUtilisateur INT PRIMARY KEY AUTO_INCREMENT,
    NomUtilisateur VARCHAR(50) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    MotDePasse VARCHAR(255) NOT NULL,
    Nom VARCHAR(50) NOT NULL,
    Prenom VARCHAR(50) NOT NULL,
    DateInscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table Petition (créateur obligatoire = gestion possible)
CREATE TABLE Petition (
    IDP INT PRIMARY KEY AUTO_INCREMENT,
    TitreP VARCHAR(200) NOT NULL,
    DescriptionP TEXT NOT NULL,
    DateAjoutP DATETIME DEFAULT CURRENT_TIMESTAMP,
    DateFinP DATE,
    NomPorteurP VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    IDUtilisateur INT NOT NULL,  -- Créateur = celui qui peut modifier/supprimer
    NombreSignatures INT DEFAULT 0,
    FOREIGN KEY (IDUtilisateur) REFERENCES Utilisateur(IDUtilisateur) ON DELETE CASCADE
);

-- Table Signature (TOUT LE MONDE peut signer, authentifié ou non)
CREATE TABLE Signature (
    IDS INT PRIMARY KEY AUTO_INCREMENT,
    IDP INT NOT NULL,
    NomS VARCHAR(50) NOT NULL,
    PrenomS VARCHAR(50) NOT NULL,
    EmailS VARCHAR(100) NOT NULL,
    PaysS VARCHAR(50),
    DateS DATETIME DEFAULT CURRENT_TIMESTAMP,
    HeureS TIME DEFAULT CURRENT_TIME,
    FOREIGN KEY (IDP) REFERENCES Petition(IDP) ON DELETE CASCADE,
    UNIQUE KEY unique_signature (IDP, EmailS)
);