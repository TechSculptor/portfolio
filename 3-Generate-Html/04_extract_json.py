import json
import os
import re
from datetime import datetime
import glob
import csv

def extraire_infos_html(contenu_html, id_counter, filename=""):
    """Extrait les informations d'un fichier HTML basés sur le template W3.CSS."""
    
    # Titre de la page (H1)
    # <h1 class="w3-jumbo w3-hide-small">{nom_grossiste}</h1>
    titre_match = re.search(r'<h1 class="w3-jumbo w3-hide-small">\s*(.+?)\s*</h1>', contenu_html, re.DOTALL)
    titre = titre_match.group(1).strip() if titre_match else "Sans nom"
    
    # Description
    # <h4>À propos</h4>\n<p>{description}</p>
    desc_match = re.search(r'<h4>À propos</h4>\s*<p>(.+?)</p>', contenu_html, re.DOTALL)
    description = desc_match.group(1).strip() if desc_match else "Description non disponible"
    
    # Catégorie
    # <p class="w3-left">{categorie}</p>
    cat_match = re.search(r'<p class="w3-left">(.+?)</p>', contenu_html)
    categorie = cat_match.group(1).strip() if cat_match else "Grossiste professionnel"
    
    # ID Product
    id_product_match = re.search(r'<include type="category" uid="(\d+)"', contenu_html)
    id_product = id_product_match.group(1) if id_product_match else None
    
    # Extraction Images
    # Logo: <center><img src="{logo_url}"
    logo_match = re.search(r'<center><img src="([^"]+)"', contenu_html)
    logo_url = logo_match.group(1) if logo_match else ""

    # Banner: <img src="{banner_url}" alt="Banner"
    banner_match = re.search(r'<img src="([^"]+)" alt="Banner"', contenu_html)
    banner_url = banner_match.group(1) if banner_match else ""
    
    # Mapping catégorie
    mapping_categories = {
        "Grossiste professionnel": "vetements",
        "Vêtements": "vetements",
        "Prêt-à-porter": "vetements",
        "Accessoires": "accessoires",
        "Bijoux": "bijoux",
        "Bijouterie": "bijoux",
        "Maroquinerie": "sacs",
        "Chaussures": "chaussures",
        "Décoration": "decoration",
        "Cosmétiques": "cosmetiques",
        "Homme": "vetements",
        "Femme": "vetements",
        "Enfant": "vetements"
    }
    categorie_finale = mapping_categories.get(categorie, "vetements")
    
    # Utiliser le nom de fichier réel pour le lien si fourni
    link_url = f"/grossistes/{filename}" if filename else f"/grossistes/{titre.lower().replace(' ', '-')}.html"
    
    objet_json = {
        "id": id_counter,
        "name": titre,
        "description": description,
        "logo": logo_url,
        "image": banner_url,
        "zone": "Local 150",
        "floor": "1",
        "category": categorie_finale,
        "createdAt": datetime.now().strftime("%Y-%m-%dT%H:%M:%S.%fZ"),
        "views": 20,
        "link": link_url,
        "id_product": id_product
    }
    
    return objet_json

def main():
    print("🔄 EXTRACTION JSON & CSV DEPUIS HTML")
    print("=" * 60)
    
    dossier_html = 'pages'
    if not os.path.exists(dossier_html):
        print(f"❌ Dossier '{dossier_html}' introuvable.")
        return
    
    fichiers_html = glob.glob(os.path.join(dossier_html, '*.html'))
    print(f"📁 {len(fichiers_html)} fichiers HTML trouvés")
    
    donnees_json = []
    
    for fichier_path in fichiers_html:
        try:
            with open(fichier_path, 'r', encoding='utf-8') as f:
                contenu = f.read()
            
            info = extraire_infos_html(contenu, len(donnees_json) + 1, os.path.basename(fichier_path))
            donnees_json.append(info)
            print(f"✅ Extrait: {info['name']} -> {info['link']}")
            
        except Exception as e:
            print(f"❌ Erreur {fichier_path}: {e}")

    # Sauvegarder JSON
    output_file = 'catalogue.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(donnees_json, f, ensure_ascii=False, indent=2)
        
    print("=" * 60)
    print(f"🎯 TERMINÉ: {len(donnees_json)} items exportés dans {output_file}")

if __name__ == "__main__":
    main()
