# 6-Visu-Energy : extraits de code (stage, 2026)

Extraits du front-end que j'ai écrits pendant mon stage de 4 mois chez **VISU Energy**, sur une plateforme web de supervision énergétique : collecte en temps réel des mesures électriques de boîtiers installés sur des sites événementiels, restituées dans une application web (tableau de bord, graphiques, alarmes, rapports).

> **Publication autorisée par VISU Energy**, à condition de ne présenter que mon propre travail. Le reste du code est privé et reste la propriété de VISU Energy.
> Ces fichiers sont des **extraits** : ils dépendent du reste de l'application (état global, routage, API Node-RED) et ne s'exécutent pas seuls.

[🎬 Voir la vidéo de démonstration](../media/visu-energy-demo.mp4) (1 min, sans son)

---

## Contexte technique

* **Front-end :** JavaScript (modules sans framework), servi par `node-red-contrib-uibuilder`, maquettes Figma.
* **Back-end :** Node-RED (API REST + WebSocket), PostgreSQL / TimescaleDB hébergé sur Supabase.
* **Données :** API cloud Talk2M (M2Web) des boîtiers eWON Flexy, interrogée par Node-RED.
* **Mon rôle :** principal contributeur du dépôt (plus de 330 commits) : interfaces d'après les maquettes Figma, version mobile, traduction FR/EN, calendrier des séquences, éditeur de plan, seuils d'alarme par projet, rapports automatisés, durcissement de la sécurité de l'API.

---

## Contenu

### 🗺️ `editeur-de-plan/` : éditeur de plan de site

Permet de dessiner le plan d'un site : zones, équipements (VISU-BOX, groupe électrogène, moteur, armoires électriques) déposés depuis une palette, connexions entre équipements, renommage et suppression. Le plan est enregistré en base par projet.

| Fichier | Rôle |
|---|---|
| `ui-actions-plan.js` | Actions de l'éditeur : glisser-déposer, connexions, renommage, suppression, sauvegarde |
| `components-plan.js` | Rendu du plan (zones, équipements, liens SVG entre équipements) |
| `settings-plan.js` | Page Réglages > Plan : sélection du parc, chargement et droits d'édition selon le rôle |

### 📅 `sequences/` : séquences de projet

Gestion des séquences d'un projet (montage, exploitation, démontage…) : création, modification, suppression, validations et statut calculé, synchronisées avec le calendrier.

| Fichier | Rôle |
|---|---|
| `settings-sequences.js` | Interface de gestion des séquences et validations des formulaires |
| `api-sequences.js` | Appels à l'API REST des séquences |

### 📡 `collecte-talk2m/` : collecte des mesures (back-end Node-RED)

> 🤝 **Réalisé en collaboration avec Omar Chrayah**, stagiaire sur le même projet.

Chaîne qui interroge les boîtiers eWON Flexy à travers l'API cloud **Talk2M (M2Web)**, transforme les réponses CSV en données structurées et les enregistre en base pour que le front-end les affiche. Les fichiers sont des exports YAML de flux Node-RED : le code JavaScript se trouve dans les champs `func` des nœuds `function`.

```
Talk2M /getewons ──▶ liste des boîtiers ──▶ upsert valises / projets (PostgreSQL)
Talk2M ParamForm $dtIV ──▶ CSV des valeurs instantanées ──▶ parsing ──▶ mesures par voie (I, P, Q, cos φ, THD…)
Talk2M ParamForm $dtHT ──▶ CSV historique (plage de dates) ──▶ rattrapage des mesures
```

| Fichier | Rôle |
|---|---|
| `api-get-ewon-list.yaml`, `moteur-1-liste-ewon.yaml` | Liste des boîtiers eWON du compte Talk2M |
| `api-get-tag-list.yaml`, `api-get-ewon-data.yaml` | Liste des tags et valeurs en temps réel d'un boîtier |
| `moteur-3-live-ewon.yaml`, `moteur-2-live-ewon-batch.yaml` | Construction des requêtes temps réel, avec repli propre si la configuration manque |
| `valise-fetch-parse-live.yaml`, `moteur-4-fetch-parse-live.yaml` | Parsing du CSV Talk2M, détection des voies, calcul des puissances totales et des cumuls |
| `api-get-ewon-history.yaml`, `moteur-2-historique-ewon.yaml` | Récupération de l'historique sur une plage de dates |
| `60-import-data-from-ewon.yaml` | Import en base : upsert des valises, projets et paramètres des voies (`INSERT … ON CONFLICT`) |

Les identifiants Talk2M ne sont jamais écrits dans les flux : ils sont lus au démarrage dans les variables d'environnement et le contexte global de Node-RED. Les valeurs par défaut présentes dans le dépôt d'origine ont été vidées dans ces extraits.

### 🌍 `i18n/` : traduction français / anglais

| Fichier | Rôle |
|---|---|
| `ui-i18n.js` | Remplacement des textes statiques par des clés de traduction, bascule FR / EN à chaud |
