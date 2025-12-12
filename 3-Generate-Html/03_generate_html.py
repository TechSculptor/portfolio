import json
import os

def generer_fichiers_html():
    print("🔄 GÉNÉRATION DES PAGES HTML")
    print("=" * 60)
    
    # 1. Charger la source (JSON)
    if not os.path.exists('catalogue.json'):
        print("❌ catalogue.json introuvable.")
        return

    with open('catalogue.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    print(f"📊 Charge: {len(data)} enregistrements depuis catalogue.json")

    # 2. Charger le template
    if not os.path.exists('template.html'):
        print("❌ template.html introuvable.")
        return

    with open('template.html', 'r', encoding='utf-8') as f:
        template_content = f.read()

    # 3. Créer dossier pages
    os.makedirs('pages', exist_ok=True)
    
    compteur = 0
    for idx, item in enumerate(data):
        try:
            # Récupération des données
            name = item.get('name', 'N/A')
            description = item.get('description', 'Pas de description.')
            logo = item.get('logo', '')
            image = item.get('image', '')
            zone = item.get('zone', 'N/A')
            floor = item.get('floor', 'N/A')
            category = item.get('category', 'Général')
            views = str(item.get('views', 0))
            
            # Application sur le template
            html_final = template_content
            
            # Simple remplace (attention à l'ordre si nécessaire)
            replacements = {
                '{name}': name,
                '{description}': description,
                '{logo}': logo,
                '{image}': image,
                '{zone}': zone,
                '{floor}': floor,
                '{category}': category,
                '{views}': views
            }
            
            for key, value in replacements.items():
                if value is None: value = ""
                html_final = html_final.replace(key, str(value))
            
            # Nom fichier : page1.html, page2.html... correspondant au lien dans le JSON
            # item['link'] est de la forme "/grossistes/page1.html"
            # On extrait le nom de fichier
            link = item.get('link', f'/grossistes/page{idx+1}.html')
            filename = os.path.basename(link) # page1.html
            
            output_path = os.path.join('pages', filename)
            
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(html_final)
            
            compteur += 1
            print(f"✅ {idx+1:02d}: {output_path}")
            
        except Exception as e:
            print(f"❌ Erreur enregistrement {idx+1}: {e}")

    print("=" * 60)
    print(f"🎯 TERMINÉ: {compteur} fichiers HTML générés dans le dossier pages/")

if __name__ == "__main__":
    generer_fichiers_html()
