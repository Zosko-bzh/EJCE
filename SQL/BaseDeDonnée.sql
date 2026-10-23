-- =========================
-- TABLES ENUM (ou équivalent)
-- =========================

CREATE TABLE Role (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO Role (nom) VALUES 
('VISITEUR'),
('ELEVE'),
('PROFESSEUR');

CREATE TABLE StatutCompte (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO StatutCompte (nom) VALUES
('ACTIF'),
('EN_PAUSE'),
('SUPPRIME');

CREATE TABLE StatutInscription (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO StatutInscription (nom) VALUES
('EN_ATTENTE'),
('VALIDEE'),
('REFUSEE');

-- =========================
-- UTILISATEUR
-- =========================

CREATE TABLE Utilisateur (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150) UNIQUE NOT NULL,
    motDePasse VARCHAR(255) NOT NULL,
    role_id INTEGER REFERENCES Role(id),
    statut_id INTEGER REFERENCES StatutCompte(id),
    dateCreation DATE
);

-- =========================
-- ELEVE
-- =========================

CREATE TABLE Eleve (
    id SERIAL PRIMARY KEY,
    utilisateur_id INTEGER UNIQUE REFERENCES Utilisateur(id) ON DELETE CASCADE,
    dateNaissance DATE,
    numeroLicence VARCHAR(100),
    progression INTEGER
);

-- =========================
-- PROFESSEUR
-- =========================

CREATE TABLE Professeur (
    id SERIAL PRIMARY KEY,
    utilisateur_id INTEGER UNIQUE REFERENCES Utilisateur(id) ON DELETE CASCADE,
    grade VARCHAR(100),
    biographie TEXT
);

-- =========================
-- DEMANDE INSCRIPTION
-- =========================

CREATE TABLE DemandeInscription (
    id SERIAL PRIMARY KEY,
    utilisateur_id INTEGER UNIQUE REFERENCES Utilisateur(id),
    dateDemande DATE,
    statut_id INTEGER REFERENCES StatutInscription(id)
);

-- =========================
-- PLANNING COURS
-- =========================

CREATE TABLE PlanningCours (
    id SERIAL PRIMARY KEY,
    jour VARCHAR(20),
    heureDebut TIME,
    heureFin TIME,
    categorieAge VARCHAR(50)
);

-- =========================
-- DOCUMENT
-- =========================

CREATE TABLE Document (
    id SERIAL PRIMARY KEY,
    nomFichier VARCHAR(255),
    type VARCHAR(50),
    urlStockage VARCHAR(255)
);

-- =========================
-- MESSAGE CONTACT
-- =========================

CREATE TABLE MessageContact (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100),
    email VARCHAR(150),
    message TEXT,
    dateEnvoi DATE
);

-- =========================
-- ANNONCE INTERNE
-- =========================

CREATE TABLE AnnonceInterne (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255),
    contenu TEXT,
    datePublication DATE,
    active BOOLEAN
);

-- =========================
-- CATEGORIE TECHNIQUE
-- =========================

CREATE TABLE CategorieTechnique (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100)
);

-- =========================
-- TECHNIQUE
-- =========================

CREATE TABLE Technique (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100),
    description TEXT,
    videoUrl VARCHAR(255),
    categorie_id INTEGER UNIQUE REFERENCES CategorieTechnique(id)
);

-- =========================
-- CEINTURE
-- =========================

CREATE TABLE Ceinture (
    id SERIAL PRIMARY KEY,
    couleur VARCHAR(50),
    ordre INTEGER
);

-- =========================
-- RELATION TECHNIQUE <-> CEINTURE (Many-to-Many)
-- =========================

CREATE TABLE Technique_Ceinture (
    technique_id INTEGER REFERENCES Technique(id) ON DELETE CASCADE,
    ceinture_id INTEGER REFERENCES Ceinture(id) ON DELETE CASCADE,
    PRIMARY KEY (technique_id, ceinture_id)
);

-- =========================
-- TABLEAU DE BORD
-- =========================

CREATE TABLE TableauDeBord (
    id SERIAL PRIMARY KEY,
    nbEleves INTEGER,
    nbInscriptionsEnAttente INTEGER,
    nbCours INTEGER,
    nbTechniques INTEGER,
    professeur_id INTEGER UNIQUE REFERENCES Professeur(id)
);

-- SQL/add_date_ceinture.sql
ALTER TABLE licence
ADD COLUMN date_ceinture DATE NULL COMMENT 'Date d\'obtention de la ceinture';
