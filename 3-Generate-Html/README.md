# 🏪 Générateur de Pages Grossistes (Workflow Simplifié)

Outil automatisé pour générer 40 pages HTML standardisées à partir de 4 fichiers sources CSV.

## 📦 Installation

### Prérequis
- Python 3.7+
- Pip

### Installation des dépendances
```bash
pip install pandas Pillow requests python-dotenv cloudinary
```

## 🚀 Utilisation (4 Étapes)

Le projet a été simplifié en 4 scripts séquentiels :

### Étape 1 : Fusion des données
Fusionne `source1.csv` à `source4.csv`, nettoie les données et génère un fichier unique standardisé.
```bash
python 01_fusion.py
```
👉 **Résultat** : `source.csv` (40 lignes)

### Étape 2 : Génération des images
Génère les logos et bannières (via Cloudinary ou en local) et met à jour le fichier source.
```bash
python 02_image.py
```
👉 **Résultat** : `source.csv` mis à jour avec URLs d'images

### Étape 3 : Génération HTML
Génère 40 pages HTML individuelles basées sur `template.html`.
```bash
python 03_generate_html.py
```
👉 **Résultat** : Dossier `pages/` contenant 40 fichiers HTML

### Étape 4 : Extraction JSON
Scanne les pages HTML générées pour créer la base de données finale.
```bash
python 04_extract_json.py
```
👉 **Résultat** : `catalogue.json`

## 📁 Structure du projet
- `source[1-4].csv` : Données brutes (10 lignes chacune)
- `template.html` : Modèle HTML pour les pages
- `01_fusion.py` : Script de nettoyage
- `02_image.py` : Script d'images
- `03_generate_html.py` : Script de rendu HTML
- `04_extract_json.py` : Script d'export final
- `pages/` : Dossier de sortie HTML