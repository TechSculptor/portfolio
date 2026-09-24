# 🎬 Lancer les projets (guide de démonstration)

[← Retour au portfolio](../README.md)

Ce guide permet de démarrer les projets numérotés (1 à 5) sur une machine Windows, macOS ou Linux, puis de tout arrêter proprement. Les commandes ont été testées sur Windows 11.

## 🌐 Sans rien installer

Trois projets sont consultables directement dans le navigateur :

| Projet | Lien |
|---|---|
| 3-Generate-Html | [Annuaire de 40 pages](https://techsculptor.github.io/portfolio/3-Generate-Html/index.html) |
| 4-Todo-List-Dev | [Todo List Angular](https://techsculptor.github.io/portfolio/4-Todo-List-Dev/demo/index.html) |
| 5-Starshop | [Démo Symfony](https://starshop-ky4b.onrender.com) : compte `demo@starshop.dev` / `starshop-demo` (réveil d'environ 1 minute si le site dormait) |

Les projets 1 et 2 ont un back-end (PHP, Node) et une base de données : ils se lancent en local.

## 🧰 Prérequis

| Outil | Nécessaire pour | Version testée |
|---|---|---|
| [Git](https://git-scm.com/) | récupérer le code | — |
| [Docker Desktop](https://www.docker.com/products/docker-desktop/) (démarré) | projets 1, 2 et 5 | 29.x |
| [Node.js](https://nodejs.org/) 20.19 ou plus + npm | projet 4 | 24.13 |
| [Python 3](https://www.python.org/) | projet 3 | 3.13 |
| PHP 8.4+ et [Composer](https://getcomposer.org/) | projet 5 | 8.5 |
| [Symfony CLI](https://symfony.com/download) (facultatif) | projet 5 | — |

```bash
git clone https://github.com/TechSculptor/portfolio.git
cd portfolio
```

## 🔌 Ports utilisés (aucun conflit entre les projets)

| Projet | Ports |
|---|---|
| 1-Cabinet-Medical | `80` (site), `8025` (MailHog), `5432` (PostgreSQL), `1025` |
| 2-Email-Tracker | `3000` (site), `8026` (MailHog), `1026` |
| 3-Generate-Html | `8001` |
| 4-Todo-List-Dev | `4200` |
| 5-Starshop | `8000` (site), `55432` (PostgreSQL) |

Si le port `80` est déjà pris (autre serveur web local), arrêtez-le avant de lancer le projet 1.

## 1-Cabinet-Medical

```bash
cd 1-Cabinet-Medical
docker compose up --build -d
```

Attendre environ 10 secondes (initialisation de la base), puis :

| Service | URL |
|---|---|
| Site | http://localhost |
| MailHog (emails de test) | http://localhost:8025 |

* **Compte administrateur :** identifiant `admin`, mot de passe `admin123`.
* **Compte patient :** *Inscription*, puis cliquer sur le lien de vérification reçu dans MailHog, puis *Connexion*.
* Parcours complet des 12 fonctionnalités : [README du projet](../1-Cabinet-Medical/README.md).
* **Arrêt :** `docker compose down -v` (le `-v` efface aussi la base).

## 2-Email-Tracker

```bash
cd 2-Email-Tracker
docker compose up --build -d
```

* Interface de démo : http://localhost:3000
* Saisir une adresse email : l'application fonctionne en **mode simulation**, aucun email n'est envoyé (rien n'arrive dans MailHog). Elle renvoie deux liens : **Simuler Ouverture** (le pixel invisible) et **Simuler Clic**.
* **Google Sheets :** l'enregistrement des ouvertures dans la feuille nécessite un compte de service Google (`credentials.json` à la racine du projet) et la variable `GOOGLE_SHEET_ID`. Sans eux, le serveur démarre quand même et affiche `GOOGLE_SHEET_ID is missing. Skipping log.` dans ses logs (`docker logs email-tracker-app`). Pour montrer le résultat réel, ouvrir directement la [feuille Google Sheets](https://docs.google.com/spreadsheets/d/1nrTaYbgPlQ6pkQJciesmsbNWtLIxSX1mYTfJ8tflIUY/edit?usp=sharing) et le [dashboard Looker Studio](https://lookerstudio.google.com/reporting/d4218795-26ec-4770-bd6d-1634ff8426f5).
* **Arrêt :** `docker compose down`. Docker peut créer un dossier vide `credentials.json` si le fichier n'existe pas : il est ignoré par git, on peut le supprimer.

## 3-Generate-Html

Les 40 pages sont déjà générées : il suffit de les servir.

```bash
cd 3-Generate-Html
python -m http.server 8001
```

* Tableau de bord : http://localhost:8001/index.html
* **Arrêt :** `Ctrl+C`.
* *(Facultatif)* Pour regénérer les pages : `pip install pandas Pillow requests python-dotenv cloudinary`, puis `python 01_fusion.py`, `02_image.py`, `03_generate_html.py` et `04_extract_json.py` dans cet ordre (détail dans le [README du projet](../3-Generate-Html/README.md)).

## 4-Todo-List-Dev

```bash
cd 4-Todo-List-Dev
npm install
npm start
```

* Application : http://localhost:4200 (compter environ 25 secondes pour l'installation, puis 15 secondes de compilation).
* **Arrêt :** `Ctrl+C`.

## 5-Starshop

```bash
cd 5-Starshop
cp .env.example .env            # PowerShell : Copy-Item .env.example .env
composer install
docker compose up -d            # PostgreSQL, Mailpit, Mercure
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console app:user:create demo@example.com --admin --password=starshop-demo
php -d memory_limit=-1 bin/console tailwind:build
symfony serve                   # laisser ce terminal ouvert (sans Symfony CLI : php -S 127.0.0.1:8000 -t public)
```

* Site : http://127.0.0.1:8000, connexion avec `demo@example.com` / `starshop-demo` (tout le site est protégé par un login).
* Pages à montrer : catalogue et position de l'ISS (`/`), fiche d'un vaisseau (clic sur son nom : droïdes, pièces, pièces chères), pièces avec recherche (`/parts`), back-office (`/admin/starship`, réservé à l'administrateur).
* Une démo en ligne existe aussi : voir la section « Sans rien installer ».
* Le `php -d memory_limit=-1 …` n'est nécessaire qu'une fois : le premier `tailwind:build` télécharge un binaire de 130 Mo qui dépasse la limite mémoire par défaut de PHP.
* Tests : `php bin/console --env=test doctrine:database:create --if-not-exists`, puis `php bin/console --env=test doctrine:migrations:migrate --no-interaction`, puis `php bin/phpunit` (30 tests).
* Sous Windows, `symfony serve -d` (arrière-plan) peut échouer avec « The process cannot access the file because it is being used by another process » : lancer simplement `symfony serve` dans un terminal dédié, ou utiliser `php -S`.
* **Arrêt :** `Ctrl+C` dans le terminal du serveur, puis `docker compose stop`.

## 🎯 Ordre conseillé pour une présentation

1. **Avant l'entretien** : ouvrir la démo en ligne du projet 5 une fois (le service gratuit s'endort et met environ 1 minute à se réveiller). Si vous voulez aussi montrer les projets 1 ou 2 en local, les lancer à l'avance (les builds Docker prennent quelques minutes la première fois) et vérifier chaque URL.
2. Commencer par les démos en ligne (projets 5, 4 et 3), qui ne dépendent d'aucune installation.
3. Enchaîner sur le projet 1 (le plus complet : rôles, emails, calendrier), puis le projet 2 avec la feuille Google Sheets.

## 🩺 En cas de problème

| Symptôme | Cause probable et solution |
|---|---|
| `docker : failed to connect to the docker API` | Docker Desktop n'est pas démarré : le lancer et attendre qu'il soit prêt. |
| `port is already allocated` / `address already in use` | Un autre programme utilise le port (voir le tableau des ports) : l'arrêter, ou `docker compose down` dans le projet concerné. |
| Projet 5 : page sans style | `tailwind:build` n'a pas été exécuté (voir l'étape correspondante). |
| Projet 5 : `Allowed memory size … exhausted` | Utiliser `php -d memory_limit=-1 bin/console tailwind:build`. |
| Projet 5 : erreur de connexion à la base | `docker compose up -d` n'est pas lancé, ou le mot de passe de `.env` ne correspond pas à `POSTGRES_PASSWORD`. |
