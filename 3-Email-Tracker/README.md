# Suivi d'Intervention Email

Un service sécurisé, minimaliste et conteneurisé pour suivre les ouvertures d'emails via un pixel 1x1. Construit avec **Node.js (Express)** et **PostgreSQL**, conçu pour s'intégrer facilement avec des outils comme Looker Studio.

## Stack Technique
- **Backend :** Node.js v18 (Express.js)
- **Base de Données :** PostgreSQL 15
- **Infrastructure :** Docker & Docker Compose
- **Sécurité :** Configuration via variables d'environnement, requêtes paramétrées

## Flux de Données
1.  **Génération :** Un identifiant de suivi unique est généré pour un email.
2.  **Intégration :** Une balise `<img src="...">` est insérée dans le code HTML de l'email.
3.  **Ouverture :** Lorsque l'utilisateur ouvre l'email, le client charge l'image depuis notre point de terminaison de suivi.
4.  **Enregistrement :** Le serveur enregistre l'événement (ID, Horodatage, IP, User Agent) de manière sécurisée dans PostgreSQL.
5.  **Réponse :** Un pixel transparent 1x1 est renvoyé au client (invisible pour l'utilisateur).

## Démarrage Rapide

### Prérequis
- Docker & Docker Compose

### Installation et Utilisation

1.  **Démarrer le Service :**
    ```bash
    docker-compose up -d --build
    ```
    L'application sera accessible sur `http://localhost:3000`.

2.  **Générer un Lien de Suivi :**
    Exécutez le script utilitaire pour obtenir un ID unique et la balise HTML prête à l'emploi :
    ```bash
    # Exécuter à l'intérieur du conteneur
    docker exec -it email-tracker-app npm run generate
    
    # Ou localement si vous avez Node.js installé
    node src/utils/generate_link.js
    ```
    *Exemple de Sortie :*
    ```text
    Unique Tracking ID: 8f4a2...
    Tracking URL:       http://localhost:3000/track?id=8f4a2...
    HTML Embed Code:    <img src="..." ... />
    ```

3.  **Tester le Suivi :**
    Copiez l'URL générée et ouvrez-la dans votre navigateur. Vous devriez voir une page blanche (le pixel transparent).

4.  **Vérifier les Données :**
    Consultez la base de données pour voir l'événement enregistré :
    ```bash
    docker exec -it email-tracker-db psql -U tracker_user -d email_tracker -c "SELECT * FROM email_opens;"
    ```

## Connexion àLooker Studio

La base de données PostgreSQL est exposée sur le port **5432**. Vous pouvez connecter Looker Studio ou d'autres outils BI en utilisant les identifiants suivants :

- **Hôte :** `localhost` (ou l'IP de votre serveur)
- **Port :** `5432`
- **Base de données :** `email_tracker`
- **Nom d'utilisateur :** `tracker_user`
- **Mot de passe :** `secure_tracker_pass` (ou voir les variables d'environnement dans `docker-compose.yml`)

### 📊 Visualisation des Données

Ce projet a pour objectif de visualiser les données liées à l'ouverture de mail à l'aide d'un tableau de bord Looker Studio.

#### 1. Mesures et Taux Généraux
* **Total Emails :** Nombre total d'emails envoyés.
* **Emails Ouverts :** Nombre d'emails ouverts.
* **Taux d'Ouverture :** Pourcentage d'ouverture par rapport aux envois.

![KPIs du Tableau de Bord](visuals/dashboard1.jpg)

#### 2. Analyse Temporelle
* **Emails ouverts dans la semaine :** Distribution des ouvertures par jour.
* **Total de clics dans la journée :** Ouvertures par heure dans la journée en cours.

![Analyse Temporelle](visuals/dashboard2.jpg)

#### 3. Rapports Détaillés
* **Les clients fidèles :** Clients les plus actifs.
* **Les liens consultés :** Liens les plus cliqués.

![Rapports Détaillés](visuals/dashboard3.jpg)

#### 4. Analyse Géographique
* **Ouverture de mails par localisation :** Carte des ouvertures par région/pays.

![Analyse Géographique](visuals/dashboard4.jpg)

#### 5. Segmentation Technique
* **Appareil :** Type d'appareil (Mobile, Desktop).
* **Navigateur :** Navigateur ou client email utilisé.

![Analyse Technique](visuals/dashboard5.jpg)
