import pandas as pd
import re
import os

# --- FONCTIONS DE NETTOYAGE ---

def nettoyer_telephone(telephone):
    """Nettoyer et formater les numéros de téléphone"""
    if pd.isna(telephone):
        return ""
    
    tel_str = str(telephone)
    # Garder seulement les chiffres
    chiffres = re.sub(r'[^\d]', '', tel_str)
    
    # Formater pour la France
    if chiffres.startswith('33'):
        chiffres = '0' + chiffres[2:]
    elif len(chiffres) == 9:
        chiffres = '0' + chiffres
    
    return chiffres

def extraire_ville_cp(adresse):
    """Extraire ville et code postal d'une adresse"""
    if pd.isna(adresse):
        return "", "93300"
    
    adresse_str = str(adresse)
    # Chercher code postal (5 chiffres)
    cp_match = re.search(r'(\d{5})', adresse_str)
    cp = cp_match.group(1) if cp_match else "93300"
    
    # Ville par défaut
    ville = "Aubervilliers"
    # Essayer de trouver la ville (simple heuristique)
    if "Paris" in adresse_str:
        ville = "Paris"
    
    return ville, cp

def standardiser_categorie(categorie):
    """Standardiser les noms de catégories"""
    if pd.isna(categorie):
        return "Non spécifié"
    
    cat = str(categorie).lower()
    
    # Mapping simple des catégories
    if any(mot in cat for mot in ['prêt', 'vêtement', 'habillement']):
        return "Prêt-à-porter"
    elif 'chaussure' in cat:
        return "Chaussures"
    elif 'maroquinerie' in cat or 'sac' in cat:
        return "Maroquinerie"
    elif 'bijou' in cat:
        return "Bijouterie"
    elif 'enfant' in cat:
        return "Enfant"
    elif 'homme' in cat:
        return "Homme"
    elif 'femme' in cat:
        return "Femme"
    elif 'accessoire' in cat:
        return "Accessoires"
    elif 'cosmétique' in cat:
        return "Cosmétiques"
    elif 'décoration' in cat or 'deco' in cat:
        return "Décoration"
    
    return categorie.title()

def nettoyer_nom_fichier(nom):
    """Nettoie le nom pour être utilisé dans les noms de fichiers/URLs."""
    if not nom:
        return "sans-nom"
    nom_clean = nom.lower().replace(' ', '-').replace('&', 'et')
    nom_clean = ''.join(c for c in nom_clean if c.isalnum() or c == '-')
    nom_clean = re.sub(r'-+', '-', nom_clean).strip('-')
    return nom_clean if nom_clean else "grossiste"

def traiter_source(nom_fichier, prefixe_id, start_id=1):
    """Traiter un fichier source et le standardiser"""
    print(f"Traitement de {nom_fichier}...")
    
    try:
        df = pd.read_csv(nom_fichier, sep=',')
    except Exception as e:
        print(f"Erreur lecture {nom_fichier}: {e}")
        return []
    
    resultats = []
    
    for idx, row in df.iterrows():
        # IDs - Séquentiel pour garantir unicité globale si besoin
        id_global = f"{prefixe_id}-{idx+start_id:03d}"
        
        # Nom et marque
        nom = ""
        # Chercher les colonnes possibles pour le nom
        for col in ['nom_grossiste', 'Nom', 'Nom Magasin', 'Nom fournisseur', 'nom entreprise', 'nom magasin']:
            if col in row and pd.notna(row[col]):
                nom = str(row[col])
                break
        
        if not nom: continue # Skip si pas de nom

        # Catégorie
        categorie = ""
        for col in ['categorie', 'Catégorie', 'Categories', 'Catagorie', 'categorie']:
            if col in row and pd.notna(row[col]):
                categorie = standardiser_categorie(row[col])
                break
        if not categorie: categorie = "Grossiste professionnel"

        # Email
        email = ""
        for col in ['email', 'Email', 'Mail', 'mail']:
            if col in row and pd.notna(row[col]):
                email = str(row[col])
                break
        
        # Téléphone
        telephone = ""
        for col in ['telephone', 'Tele', 'tele', 'Numéro de téléphone', 'Telephone']:
            if col in row and pd.notna(row[col]):
                telephone = nettoyer_telephone(row[col])
                break
        
        # Adresse
        adresse_complete = ""
        ville = ""
        code_postal = ""
        
        for col in ['adresse', 'Adresse', 'Rue', 'rue']:
            if col in row and pd.notna(row[col]):
                adresse = str(row[col])
                if adresse.lower() != 'n/a':
                    ville, code_postal = extraire_ville_cp(adresse)
                    adresse_complete = f"{adresse}, {code_postal} {ville}"
                break
        
        if not adresse_complete:
            ville, code_postal = extraire_ville_cp("")
            adresse_complete = f"70 Avenue Victor Hugo, {code_postal} {ville}"
        
        # Site web
        site_web = ""
        for col in ['site_web', 'Site', 'site', 'Site_Web']:
            if col in row and pd.notna(row[col]):
                site_web = str(row[col])
                break
        if not site_web: site_web = "#"

        # Description
        description = ""
        for col in ['description', 'Description']:
            if col in row and pd.notna(row[col]):
                description = str(row[col])
                break
        if not description: 
            description = f"Découvrez {nom}, votre grossiste de référence en {categorie}. Large choix de produits de qualité pour les professionnels."

        # Annee
        annee = "2010"
        for col in ['annee_fondation', 'Annee', 'annee']:
             if col in row and pd.notna(row[col]):
                annee = str(row[col])

        # Créer la ligne standardisée pour source.csv final
        ligne = {
            'id_section': id_global, # Important pour HTML generation
            'nom_grossiste': nom,
            'nom_clean': nettoyer_nom_fichier(nom),
            'categorie': categorie,
            'email': email,
            'telephone': telephone,
            'adresse': adresse_complete,
            'ville': ville,
            'code_postal': code_postal,
            'site_web': site_web,
            'description': description,
            'annee_fondation': annee,
            'note': '4.5', # Valeur par défaut
            'pays': 'France',
            'montant_minimum': '100€',
            'delai_livraison': '24/48h',
            # Colonnes images (vides pour l'instant, remplies par 02_image.py)
            'logo_url': f"https://via.placeholder.com/150?text={nom[:2]}",
            'banner_url': f"https://via.placeholder.com/1000x300?text={nom}"
        }
        
        resultats.append(ligne)
    
    return resultats

def main():
    print("🔄 FUSION ET STANDARDISATION DES SOURCES")
    print("=" * 60)
    
    toutes_donnees = []
    
    # Traiter les 4 sources
    sources = ['source1.csv', 'source2.csv', 'source3.csv', 'source4.csv']
    
    compteur_id = 1
    for fichier in sources:
        if os.path.exists(fichier):
            donnees_source = traiter_source(fichier, "AUTO", compteur_id)
            toutes_donnees.extend(donnees_source)
            compteur_id += len(donnees_source)
            print(f"✅ {fichier} : {len(donnees_source)} lignes ajoutées")
        else:
            print(f"❌ {fichier} non trouvé")
    
    # Créer le DataFrame final
    if toutes_donnees:
        df_final = pd.DataFrame(toutes_donnees)
        
        # Sauvegarder
        df_final.to_csv('source.csv', index=False, encoding='utf-8')
        print("=" * 60)
        print(f"✅ terminé : source.csv généré avec {len(df_final)} enregistrements.")
    else:
        print("❌ Aucune donnée à sauvegarder.")

if __name__ == "__main__":
    main()