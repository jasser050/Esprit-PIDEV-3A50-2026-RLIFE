# RLIFE - Student Productivity Platform

## Description

RLIFE est une plateforme web développée dans le cadre du projet PIDEV. Elle aide les étudiants à organiser leur vie académique grâce à des outils de gestion des cours, révisions, projets collaboratifs, planification et bien-être.

## Technologies utilisées

### Backend

* PHP 8.2
* Symfony 6.4
* Doctrine ORM

### Frontend

* Twig
* JavaScript (ES6+)
* TailwindCSS 3.4
* GSAP

### Base de données

* MySQL 8.0

## Fonctionnalités principales

* Gestion des cours et matières
* Gestion des révisions et flashcards
* Gestion des projets collaboratifs
* Planification des séances
* Bien-être étudiant
* Gamification
* Assistant IA

## Prérequis

* PHP 8.2+
* Composer
* MySQL 8.0+
* Node.js 18+
* Symfony CLI (optionnel)

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/USERNAME/Esprit-PI-Classe-2526-RLIFE.git
cd RLIFE
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances Frontend

```bash
npm install
npm run build
```

### 4. Configurer l'environnement

```bash
cp .env.example .env.local
```

Modifier les paramètres de connexion à la base de données dans `.env.local`.

### 5. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les données de test (optionnel)

```bash
php bin/console doctrine:fixtures:load
```

## Lancement

### Avec Symfony CLI

```bash
symfony serve
```

### Sans Symfony CLI

```bash
php -S localhost:8000 -t public/
```

Application disponible sur :

```text
http://127.0.0.1:8000
```

## Variables d'environnement

Voir le fichier :

```text
.env.example
```

## Structure du projet

```text
src/
├── Controller/
├── Entity/
├── Repository/
├── Service/
└── Security/

templates/
assets/
config/
migrations/
tests/
```

## Documentation

La documentation technique est disponible dans le dossier :

```text
docs/
```

## Démonstration

Vidéo : [Ajouter le lien]

Captures d'écran : dossier `demo/`

Déploiement : [Ajouter le lien ou "Non disponible"]

## Équipe

| Nom             | Module                          |
| --------------- | ------------------------------- |
| Jasser Balti    | Architecture & Authentification |
| Eya Dhrioua     | Bien-être étudiant & Quiz       |
| Yassine Mlaouah | Planification                   |
| Nermine Karoui  | Gestion des cours               |
| Samar Masmoudi  | Projets collaboratifs           |
| Maram Mohamed   | Révisions & Flashcards          |

## Commandes utiles

```bash
php bin/phpunit
vendor/bin/phpstan analyse
php bin/console cache:clear
```

## Licence

Projet académique réalisé dans le cadre du PIDEV 2025-2026 à ESPRIT.
