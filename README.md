# 🎓 PIDEV - RLIFE

> Application de productivité étudiant - Gestion de vie étudiante

[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=flat&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php)](https://www.php.net)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4-06B6D4?style=flat&logo=tailwind-css)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Actif-success.svg)]()

---

## 📝 Description

**RLIFE** est une application web de productivité conçue pour les étudiants, développée dans le cadre du projet PIDEV (Projet d'Intégration Developpement). Elle permet aux étudiants de gérer leurs études, leurs projets, leurs révisions et leur bien-être de manière centralisée.

### Fonctionnalités principales

- 📚 **Gestion des cours** - Création et organisation des matières
- 📝 **Révisions intelligentes** - Cartes mémoire avec apprentissage spacing
- 📋 **Gestion des projets** - Suivi des projets collaboratifs
- 📅 **Planification** - Calendrier et planification des séances
- 💬 **Collaboration** - Travail d'équipe sur les devoirs
- 🧘 **Bien-être étudiant** - Outils de relaxation et suivi du moral
- 🎮 **Gamification** - Système de points et achievements
- 🤖 **Assistant IA** - Aide intelligente pour les révisions

---

## 🛠️ Technologies utilisées

### Backend
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)
![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=for-the-badge&logo=symfony)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)

### Frontend
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.4-06B6D4?style=for-the-badge&logo=tailwind-css)
![GSAP](https://img.shields.io/badge/GSAP-3.12-88CE02?style=for-the-badge)

### Outils & Services
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker)
![Webpack](https://img.shields.io/badge/Webpack-8DD6F8?style=for-the-badge&logo=webpack)
![PHPUnit](https://img.shields.io/badge/PHPUnit-11-FF9900?style=for-the-badge)
![PHPStan](https://img.shields.io/badge/PHPStan-Level%205-2E5CAA?style=for-the-badge)

---

## 📦 Structure du projet

```
PIDEV/
├── assets/              # Ressources frontend (JS, CSS, images)
├── bin/                 # Scripts Symfony
├── config/              # Configuration du projet
├── migrations/          # Migrations Doctrine
├── public/             # Fichiers publics (build, images)
├── src/
│   ├── Controller/     # Contrôleurs Symfony
│   ├── Entity/         # Entités Doctrine
│   ├── Repository/     # Repositories Doctrine
│   ├── Service/        # Services métier
│   └── Security/       # Authentification
├── templates/          # Templates Twig
├── tests/              # Tests unitaires
├── vendor/            # Dépendances Composer
└── ...
```

---

## 🚀 Installation

### Prérequis

- PHP 8.2+
- Composer
- MySQL 8.0+ / MariaDB
- Node.js 18+
- XAMPP (pour le développement local)

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone https://github.com/jasser050/PIDEV.git
cd PIDEV
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Configurer les variables d'environnement**
```bash
cp .env .env.local
# Modifier les paramètres de base de données
```

4. **Installer les dépendances frontend**
```bash
npm install
npm run build
```

5. **Créer la base de données**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

6. **Lancer le serveur**
```bash
php bin/console server:run
```

---

## 👥 Équipe de développement

| Membre | Rôle | Contribution |
|--------|------|--------------|
| **Jasser Balti** | Développeur | Architecture, Authentification, Dashboard |
| **Eya Dhrioua** | Développeur | Bien-être étudiant, Quiz |
| **Yassine Mlaouah** | Développeur | Planification et séances |
| **Nermine Karoui** | Développeur | Cours, Matières |
| **Samar Masmoudi** | Développeur | Projets collaboratifs |
| **Maram Mohamed** | Développeur | Gestion des révisions, Cartes mémoire |

### Encadrants
- **Professeur** - Direction du projet PIDEV
- **École** - Institution

---

## 📱 Captures d'écran

### Page de connexion
![Login](screenshots/login.png)

### Dashboard
![Dashboard](screenshots/dashboard.png)

### Système de révision
![Revision](screenshots/revision.png)

---

## 🔧 Commandes utiles

```bash
# Lancer les tests
php bin/phpunit

# Analyse statique du code
vendor/bin/phpstan analyse

# Nettoyer le cache
php bin/console cache:clear

# Créer une entité
php bin/console make:entity

# Créer un contrôleur
php bin/console make:controller
```

---

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 🙏 Remerciements

- Équipe pédagogique PIDEV
- Communauté Symfony
- Contributeurs open source

---

<div align="center">

⭐️ *N'hésitez pas à star ce projet si il vous a été utile !* ⭐️

</div>
