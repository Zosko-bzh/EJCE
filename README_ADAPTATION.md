# Objectifs & Adaptation de l'Architecture EJCE

Ce document explique comment le Front-end (inspiré de la maquette Netlify) va communiquer avec le Back-end (Symfony) et la base de données (SQL fournie).

## 1. La Navigation et les Pages Principales

- **Accueil (`/`)** : Affiche les informations globales et les "Statistiques du club".
  - *Adaptation Backend* : Appel à lexistant `TableauDeBord` pour remonter dynamiquement le `nbEleves`, `nbCours`, `nbTechniques`, etc. afin d'animer les chiffres de la section Statistiques de la maquette.

- **Le Judo (`/judo.html`)** : Présentation du judo et des techniques.
  - *Adaptation Backend* : Utilisation des tables `CategorieTechnique`, `Technique`, et `Ceinture`. L'API (`/api/techniques`) renverra ces informations.

- **Équipe pédagogique (`/equipe.html`)** :
  - *Adaptation Backend* : Récupération depuis les tables `Professeur` et `Utilisateur`. La route (`/api/professeurs`) exposera leurs biographies et grades.

- **Contact (`/contact.html`)** :
  - *Adaptation Backend* : Un POST sur `/api/contact` permettra de sauvegarder le formulaire dans la table `MessageContact`.

## 2. L'Espace "Adhérer au club" et la "Newsletter"

- **Adhésion** :
  - *Adaptation Backend* : Sur clic, cela appellera une API pour insérer dans `DemandeInscription`. L'état initial sera `EN_ATTENTE` (via la table `StatutInscription`). Le back-end associera le nouvel utilisateur à la table `Utilisateur` avec un `statut_id` lié à `EN_PAUSE` s'il n'a pas encore payé.

## 3. L'Espace Licencié (Bouton principal Rouge)

La maquette met en évidence le bouton rouge "espace licencié".
- **Authentification (`/login.html`)** :
  - *Adaptation Backend* : Utilisation du composant *Security* de Symfony couplé à la table `Utilisateur` (motDePasse hashé). 
  - *Autorisations* : Basées sur la table `Role` (`VISITEUR`, `ELEVE`, `PROFESSEUR`). Les JWT Tokens ou des Cookies de session gèreront la connexion Front/Back.

## 4. Prise en charge Docker & PHP

J'ai recréé la page d'accueil et le style CSS de la maquette dans le dossier `front/`. 
Lorsque Symfony sera installé dans le dossier `back/`, les Entity PHP se mapperont 1-pour-1 sur le fichier `SQL/BaseDeDonnée.SQL` que tu m'as partagé. Doctrine ORM gèrera le lien entre ton code et ton MySQL instancié par Docker.

Tout est prêt pour démarrer le développement logique !
