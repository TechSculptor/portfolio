# 🚀 Portfolio Technique | TechSculptor

**Développeur Junior Back-End (PHP/PostgreSQL) et Front-End (ReactJS/JavaScript/HTML5/CSS3).**

Ce répertoire démontre ma capacité à concevoir, développer et déployer des solutions simples et évolutives.

## 🛠️ Compétences Techniques Clés

* **Backend & API :** PHP (Symfony, Doctrine), Sécurité (PDO, hachage, CSRF, rôles), Docker, n8n (Workflows).
* **Frontend & UX :** Angular (Signals, Standalone Components), ReactJS, TypeScript, JavaScript, HTML5/CSS3 (W3.CSS, Tailwind), Responsive.
* **Base de Données & BI :** PostgreSQL, Modélisation SQL, Looker Studio (pour la visualisation des données).
* **Déploiement & Qualité :** Docker & Docker Compose, Render, GitHub Actions (CI), tests PHPUnit, variables d'environnement.

**Sommaire :** [Projets de référence](#-projets-de-référence) · [Projets connexes](#-projets-connexes) · [Lancer les projets](docs/LANCER-LES-PROJETS.md) · [Aperçu visuel](#-aperçu-visuel)

---

## 🏆 Projets de référence

Les projets sur lesquels je vous invite à passer du temps : ils sont documentés, testables en quelques commandes et représentatifs de ce que je sais faire.

### 5-Starshop : Boutique de vaisseaux (Symfony 8)

![Starshop CI](https://github.com/TechSculptor/portfolio/actions/workflows/starshop-ci.yaml/badge.svg)

Une boutique / atelier de réparation de vaisseaux spatiaux fictif, pour démontrer la conception d'une application web complète avec PHP, Symfony et PostgreSQL.

* **Stack :** Symfony 8.1, Doctrine ORM (migrations), PostgreSQL, Twig, Stimulus/Turbo, Tailwind CSS, Docker Compose, PHPUnit, GitHub Actions.
* **Points Forts :**
    - 🔐 Authentification `form_login`, rôles `ROLE_USER` / `ROLE_ADMIN`, protection CSRF et limitation des tentatives de connexion.
    - 🗃️ Relations Doctrine complètes (dont une entité de jointure portant une date d'assignation), pagination, slugs et horodatage automatiques.
    - 🌱 Jeux de données générés avec Zenstruck Foundry, commandes console dédiées (création d'utilisateur, check-in, rapport).
    - 🌍 Intégration de l'API publique de position de l'ISS, mise en cache et tolérante aux pannes.
    - 🌐 API JSON en lecture seule et back-office CRUD réservé aux administrateurs.
    - 🧪 30 tests PHPUnit et intégration continue (style de code, linters, migrations, tests).
    - 🐳 `Dockerfile` et blueprint Render : la démo est en ligne.

[**✨ Voir la démo en ligne**](https://starshop-ky4b.onrender.com) : compte de démonstration en lecture seule `demo@starshop.dev` / `starshop-demo` (hébergement gratuit : comptez environ 1 minute au réveil, les données sont recréées à chaque démarrage).

[**📂 Voir le code**](https://github.com/TechSculptor/portfolio/tree/main/5-Starshop) · [**📖 Documentation du projet**](5-Starshop/README.md) · [**🚀 Le lancer en local**](docs/LANCER-LES-PROJETS.md#5-starshop)

![Starshop - Fiche vaisseau](Screen/Starshop3-vaisseau.png)

### 1-Cabinet-Medical : Gestion de Cabinet Médical (PHP / PostgreSQL)

Un système de gestion de rendez-vous complet simulant un environnement médical réel, conçu pour répondre à 12 critères fonctionnels précis (6 minimaux, 6 optionnels).

* **Objectif :** Une plateforme sécurisée pour patients, médecins et administrateurs.
* **Stack :** PHP, PostgreSQL, Docker, W3.CSS, TCPDF, MailHog.
* **Points Forts :**
    - 🔐 Inscription avec email de vérification, authentification sécurisée, rôles (patient, administrateur).
    - 📅 Prise et annulation de rendez-vous, planning des créneaux libres mis à jour en AJAX sans rechargement.
    - 📄 Récapitulatif de rendez-vous en PDF.
    - 🛠️ Espace administrateur : gestion des médecins et planning de tous les médecins.
    - 📐 Conception documentée : diagramme de cas d'utilisation, MCD et MLD.

[**📂 Voir le code**](https://github.com/TechSculptor/portfolio/tree/main/1-Cabinet-Medical) · [**📖 Les 12 fonctionnalités et comment les tester**](1-Cabinet-Medical/README.md) · [**🚀 Le lancer en local**](docs/LANCER-LES-PROJETS.md#1-cabinet-medical)

### 4-Todo-List-Dev : Todo List Angular pour Développeurs

Une todo-list conçue spécifiquement pour l'organisation quotidienne d'un développeur, construite avec Angular 21 (Signals, composants standalone, nouvelle syntaxe de contrôle de flux).

* **Objectif :** Aller au-delà du CRUD basique pour explorer un vrai cas d'usage produit (priorités, catégorisation, suivi de sous-tâches, rappels adaptatifs).
* **Stack :** Angular 21, TypeScript, Signals (`signal`/`computed`/`effect`), `localStorage`, Vitest.
* **Points Forts :**
    - 🏗️ Code organisé par responsabilité (types, utilitaires de dates, données/persistance, statistiques, composant) plutôt qu'un fichier unique.
    - 🔁 État 100% réactif via Signals, sans RxJS, avec immutabilité systématique des mises à jour.
    - 🔔 Rappels dont la fréquence s'adapte à la proximité de l'échéance.
    - 🤖 Suggestion de documentation façon agent IA (mockée côté client par design, pour ne jamais exposer de clé API dans le navigateur).
    - 💾 Persistance `localStorage` avec migration de schéma pour les anciennes données sauvegardées.

[**✨ Voir la démo en ligne**](https://techsculptor.github.io/portfolio/4-Todo-List-Dev/demo/index.html) · [**📂 Voir le code**](https://github.com/TechSculptor/portfolio/tree/main/4-Todo-List-Dev) · [**🚀 Le lancer en local**](docs/LANCER-LES-PROJETS.md#4-todo-list-dev)

---

## 🧩 Projets connexes

Des projets plus ciblés ou plus expérimentaux, qui complètent les projets de référence.

| Projet | Ce que c'est | Stack | Liens |
|---|---|---|---|
| **2-Email-Tracker** | Micro-service qui suit les ouvertures d'emails grâce à un pixel invisible, avec visualisation dans Looker Studio. | Node.js / Express, Docker, Google Sheets API | [Code](https://github.com/TechSculptor/portfolio/tree/main/2-Email-Tracker) · [Google Sheet](https://docs.google.com/spreadsheets/d/1nrTaYbgPlQ6pkQJciesmsbNWtLIxSX1mYTfJ8tflIUY/edit?usp=sharing) · [Dashboard](https://lookerstudio.google.com/reporting/d4218795-26ec-4770-bd6d-1634ff8426f5) · [Le lancer](docs/LANCER-LES-PROJETS.md#2-email-tracker) |
| **3-Generate-Html** | Pipeline en 4 étapes qui génère 40 pages web responsive à partir de fichiers CSV, avec export JSON pour une API. | Python (Pandas), HTML/CSS, JSON | [Code](https://github.com/TechSculptor/portfolio/tree/main/3-Generate-Html) · [Rendu final](https://techsculptor.github.io/portfolio/3-Generate-Html/index.html) · [Le lancer](docs/LANCER-LES-PROJETS.md#3-generate-html) |
| **Cryptobot** | Exploration en Rust, à deux, de l'API de la plateforme KuCoin : récupération de chandeliers (BTC, ETH, KCS) et essais d'analyse technique. Environ 8 400 lignes réparties en une quinzaine de petits programmes. | Rust, tokio, reqwest | Dépôt privé, non publié |
| **Exercism** | Exercices de programmation pour progresser régulièrement. | TypeScript et autres | [Mon profil Exercism](https://exercism.org/profiles/Kozmo45) |

**▶ [Guide pour lancer tous les projets](docs/LANCER-LES-PROJETS.md)** : prérequis, ports, étapes de chaque projet, dépannage.

### 🔒 Note sur la Confidentialité

Le code source de ces projets a été entièrement anonymisé. Tous les noms d'entreprise, la logique métier propriétaire, et les identifiants de sécurité ont été retirés pour des raisons de confidentialité. Les projets présentés ici sont des versions génériques destinées à démontrer mes compétences techniques à des fins de recrutement.

---

## ⭐ Recommandation Professionnelle

> "Thomas a montré une **bonne motivation** et une réelle curiosité pour le métier de développeur informatique. Il s’est investi dans les missions confiées et a su **s’adapter progressivement à notre environnement de travail**. Son attitude a été **respectueuse, sérieuse** et il a manifesté un réel intérêt pour comprendre les outils, les méthodes et les enjeux liés au développement web et à l’organisation d’un projet technique. Cette immersion s’est déroulée dans de bonnes conditions et a été globalement positive."
>

> **— Quin Axel, Chef de projet, Easy Tech**

---

## 📸 Aperçu Visuel

Quelques captures des projets réalisés :

![Portfolio Overview 1](Screen/Portfolio1.png)
*Vue d'ensemble et accueil*

![Portfolio Overview 2](Screen/Portfolio2.png)
*Code des fonctionnalités médicales*

![Portfolio Overview 3](Screen/Portfolio3.png)
*Code de gestion et tracking*

![Portfolio Overview 4](Screen/Portfolio4.png)
*Génération automatique de catalogue*

![Portfolio Overview 5](Screen/Portfolio5.png)
*Back-office et administration*

### 5-Starshop (Symfony)

![Starshop - Connexion](Screen/Starshop1-login.png)
*Authentification `form_login` : tout le site est derrière un login*

![Starshop - File de réparation](Screen/Starshop2-catalogue.png)
*Catalogue paginé des vaisseaux et position de l'ISS (API externe)*

![Starshop - Fiche vaisseau](Screen/Starshop3-vaisseau.png)
*Fiche détaillée d'un vaisseau*

![Starshop - Pièces](Screen/Starshop4-pieces.png)
*Catalogue de pièces avec recherche*

![Starshop - Administration](Screen/Starshop5-admin.png)
*Back-office CRUD réservé au rôle `ROLE_ADMIN`*
