# Projet EJCE

## Architecture
Ce projet suit une architecture Front-End et Back-End séparés :
- **Front-End** : HTML, CSS, JS (dossier `/front`).
- **Back-End** : PHP via Symfony (dossier `/back`).
- **Base de données** : MySQL (Relationnel) & MongoDB (NoSQL).
- **Environnement de travail** : 100% Docker (`docker-compose.yml`).
- **Dossier SQL** : Fichiers `.sql` bruts dans le dossier `/SQL`.

## Commandes pour démarrer

1. Assurez-vous d'avoir Docker Desktop lancé sur votre machine.
2. Démarrez les conteneurs :
   ```bash
   docker-compose up -d --build
   ```

3. Le site web (Front) sera accessible sur : http://localhost
4. Le Back-End (API) est accessible sur : http://localhost/api/status

## Installation de Symfony (Back-End)

Actuellement, le projet contient un Back-End temporaire (PHP natif) à des fins de test. Pour installer formellement Symfony :

1. Entrez dans le conteneur PHP :
   ```bash
   docker-compose exec php bash
   ```
2. Installez Symfony via Composer (attention, le dossier `/back` ne doit pas être vide pour Symfony mais Composer écrase le projet si forcé ou via git init) :
   ```bash
   composer create-project symfony/skeleton my_project_name
   # ou plus simplement, pour installer symfony dans le dossier existant (créer un backup du index.php temporaire) :
   # (depuis le shell bash, ou plus simple, utiliser ce one-liner depuis Windows)
   ```

**Alternativement (Directement depuis Windows vers le conteneur)**:
Supprimez l'actuel `index.php` temporaire et créez le projet Symfony :
```bash
docker-compose exec php composer create-project symfony/skeleton .
```

## Bases de données

Les identifiants (`root` pour mysql, ou mongodb sans mdp) sont configurés dans `docker-compose.yml`.
Pour importer le contenu du dossier `/SQL`, placez vos fichiers dedans, puis dans votre terminal MySQL (ou avec un outil comme DBeaver/HeidiSQL connecté au port local `3306` de localhost), importez les données. MongoDB est ouvert sur le `27017`.
